<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Enums\CourseClassType;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final readonly class ImportCourseSelectSimulation
{
    /**
     * Exam categories whose courses have no centrally scheduled exam.
     *
     * @var list<string>
     */
    private const array NO_CENTRAL_EXAM_CATEGORIES = ['隨班評量', '全遠距', '微學分'];

    /**
     * Courses whose midterm is assessed in class while the final stays in
     * the time slot they are also listed under.
     */
    private const string MIDTERM_IN_CLASS_CATEGORY = '期中隨班評量';

    private const array EXAM_DAYS = ['週六' => 'saturday', '週日' => 'sunday'];

    /**
     * Import course-selection-notice (選課注意事項) sessions from
     * resources/data/course-select-sim.json as tentative CourseClass +
     * ClassSchedule rows, so the rest of the app can treat them like any
     * other class. Courses listed under the 全遠距/微學分 exam categories
     * have all of their sessions retyped to full_remote/micro_credit
     * (keeping each session's own time/dates), since that designation
     * describes the course's format rather than any one session. Courses
     * with no video-session times fall back to a placeholder class.
     * Idempotent: re-running updates existing tentative classes and drops
     * dates no longer present in the source data.
     *
     * Exam dates and times come from `examDates` plus the `exam` map: a
     * `週六 13:30~14:40`-style key puts its courses on that day's midterm
     * and final in that slot, {@see self::MIDTERM_IN_CLASS_CATEGORY} drops
     * the midterm of courses that only sit the central final, and
     * {@see self::NO_CENTRAL_EXAM_CATEGORIES} clear any exam schedule.
     *
     * @return array{terms: int, classes: int, exams: int}
     */
    public function __invoke(?string $term = null): array
    {
        $path = resource_path('data/course-select-sim.json');

        if (! File::exists($path)) {
            return ['terms' => 0, 'classes' => 0, 'exams' => 0];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            return ['terms' => 0, 'classes' => 0, 'exams' => 0];
        }

        $terms = $term !== null ? [$term] : array_keys($data);

        $termsImported = 0;
        $classesUpserted = 0;
        $examsUpdated = 0;

        foreach ($terms as $termToImport) {
            if (! is_string($termToImport)) {
                continue;
            }

            $classesUpserted += $this->importTerm($data, $termToImport);
            $examsUpdated += $this->importExams($data, $termToImport);
            $termsImported++;
        }

        return ['terms' => $termsImported, 'classes' => $classesUpserted, 'exams' => $examsUpdated];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importTerm(array $data, string $term): int
    {
        if (! isset($data[$term]['courses'], $data[$term]['videoSessions']) || ! is_array($data[$term]['courses']) || ! is_array($data[$term]['videoSessions'])) {
            return 0;
        }

        $courseNames = $data[$term]['courses'];
        $videoSessions = $data[$term]['videoSessions'];

        $typesByLabel = collect(CourseClassType::cases())
            ->mapWithKeys(fn (CourseClassType $type) => [$type->label() => $type]);

        $formatOverrides = $this->resolveFormatOverrides($data, $term, $typesByLabel);

        $classesUpserted = 0;

        foreach ($videoSessions as $simCourseId => $sessions) {
            $name = $courseNames[$simCourseId] ?? null;

            if (! is_string($name) || ! is_array($sessions)) {
                continue;
            }

            $course = $this->findOrCreateCourse($term, $name);
            $formatOverride = $formatOverrides[$simCourseId] ?? null;
            unset($formatOverrides[$simCourseId]);

            foreach ($sessions as $label => $session) {
                $originalType = $typesByLabel->get($label);

                if (! $originalType instanceof CourseClassType || ! is_array($session)) {
                    continue;
                }

                if (! isset($session['start'], $session['end'], $session['dates']) || ! is_array($session['dates'])) {
                    continue;
                }

                $this->upsertTentativeClass(
                    $course,
                    $formatOverride ?? $originalType,
                    'NOTICE-'.strtoupper($originalType->value),
                    (string) $session['start'],
                    (string) $session['end'],
                    array_values(array_map('strval', $session['dates'])),
                );
                $classesUpserted++;
            }
        }

        // Courses whose format is only known from the exam categories (no
        // video-session times available) get a single placeholder class.
        foreach ($formatOverrides as $simCourseId => $type) {
            $name = $courseNames[$simCourseId] ?? null;

            if (! is_string($name)) {
                continue;
            }

            $course = $this->findOrCreateCourse($term, $name);

            $this->upsertTentativeClass($course, $type, 'NOTICE-'.strtoupper($type->value), '00:00', '00:00', []);
            $classesUpserted++;
        }

        return $classesUpserted;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importExams(array $data, string $term): int
    {
        $courseNames = $data[$term]['courses'] ?? null;
        $examCategories = $data[$term]['exam'] ?? null;
        $examDates = $data[$term]['examDates'] ?? null;

        if (! is_array($courseNames) || ! is_array($examCategories) || ! is_array($examDates)) {
            return 0;
        }

        $midtermInClass = array_map('strval', (array) ($examCategories[self::MIDTERM_IN_CLASS_CATEGORY] ?? []));

        /** @var array<string, array{midterm_date: ?string, final_date: ?string, exam_time_start: ?string, exam_time_end: ?string}> $schedules */
        $schedules = [];

        foreach ($examCategories as $label => $courseIds) {
            if (! is_array($courseIds)) {
                continue;
            }

            if (in_array($label, self::NO_CENTRAL_EXAM_CATEGORIES, true)) {
                foreach ($courseIds as $simCourseId) {
                    $schedules[(string) $simCourseId] = [
                        'midterm_date' => null,
                        'final_date' => null,
                        'exam_time_start' => null,
                        'exam_time_end' => null,
                    ];
                }

                continue;
            }

            $slot = $this->parseExamSlot((string) $label, $examDates);

            if ($slot === null) {
                continue;
            }

            foreach ($courseIds as $simCourseId) {
                $simCourseId = (string) $simCourseId;

                $schedules[$simCourseId] = [
                    'midterm_date' => in_array($simCourseId, $midtermInClass, true) ? null : $slot['midterm'],
                    'final_date' => $slot['final'],
                    'exam_time_start' => $slot['start'],
                    'exam_time_end' => $slot['end'],
                ];
            }
        }

        $updated = 0;

        foreach ($schedules as $simCourseId => $schedule) {
            $name = $courseNames[$simCourseId] ?? null;

            if (! is_string($name)) {
                continue;
            }

            $course = $this->findOrCreateCourse($term, $name);
            $course->midterm_date = $schedule['midterm_date'];
            $course->final_date = $schedule['final_date'];
            $course->exam_time_start = $schedule['exam_time_start'];
            $course->exam_time_end = $schedule['exam_time_end'];
            $course->saveOrFail();
            $updated++;
        }

        return $updated;
    }

    /**
     * Read a `週六 13:30~14:40` exam category into that day's dates and a
     * zero-padded time range, as the official exam import stores them.
     *
     * @param  array<string, mixed>  $examDates
     * @return array{midterm: ?string, final: ?string, start: string, end: string}|null
     */
    private function parseExamSlot(string $label, array $examDates): ?array
    {
        if (! preg_match('/^(週六|週日)\s+(\d{1,2}):(\d{2})\s*[~～-]\s*(\d{1,2}):(\d{2})$/u', $label, $matches)) {
            return null;
        }

        $day = self::EXAM_DAYS[$matches[1]];
        $midterm = $examDates['mid'][$day] ?? null;
        $final = $examDates['final'][$day] ?? null;

        return [
            'midterm' => is_string($midterm) ? $midterm : null,
            'final' => is_string($final) ? $final : null,
            'start' => sprintf('%02d:%s', (int) $matches[2], $matches[3]),
            'end' => sprintf('%02d:%s', (int) $matches[4], $matches[5]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<string, CourseClassType>  $typesByLabel
     * @return array<string, CourseClassType>
     */
    private function resolveFormatOverrides(array $data, string $term, Collection $typesByLabel): array
    {
        $examCategories = $data[$term]['exam'] ?? null;

        if (! is_array($examCategories)) {
            return [];
        }

        $overrides = [];

        foreach ($examCategories as $label => $courseIds) {
            $type = $typesByLabel->get($label);

            if (! $type instanceof CourseClassType || ! is_array($courseIds)) {
                continue;
            }

            foreach ($courseIds as $simCourseId) {
                $overrides[(string) $simCourseId] = $type;
            }
        }

        return $overrides;
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function upsertTentativeClass(Course $course, CourseClassType $type, string $code, string $start, string $end, array $dates): void
    {
        $courseClass = CourseClass::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'code' => $code,
                'is_tentative' => true,
            ],
            [
                'type' => $type->value,
                'start_time' => $start,
                'end_time' => $end,
                'teacher_name' => '',
                'link' => '',
            ],
        );

        $courseClass->schedules()->whereNotIn('date', $dates)->delete();

        foreach ($dates as $date) {
            ClassSchedule::query()->updateOrCreate([
                'class_id' => $courseClass->id,
                'date' => $date,
            ]);
        }
    }

    private function findOrCreateCourse(string $term, string $name): Course
    {
        return Course::query()
            ->where('term', $term)
            ->whereNameMatches($name)
            ->orderBy('id')
            ->first()
            ?? Course::query()->create([
                'name' => $name,
                'term' => $term,
            ]);
    }
}
