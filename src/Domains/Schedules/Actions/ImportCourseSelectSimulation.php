<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Enums\CourseClassType;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final readonly class ImportCourseSelectSimulation
{
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
     * @return array{terms: int, classes: int}
     */
    public function __invoke(?string $term = null): array
    {
        $path = resource_path('data/course-select-sim.json');

        if (! File::exists($path)) {
            return ['terms' => 0, 'classes' => 0];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            return ['terms' => 0, 'classes' => 0];
        }

        $terms = $term !== null ? [$term] : array_keys($data);

        $termsImported = 0;
        $classesUpserted = 0;

        foreach ($terms as $termToImport) {
            if (! is_string($termToImport)) {
                continue;
            }

            $classesUpserted += $this->importTerm($data, $termToImport);
            $termsImported++;
        }

        return ['terms' => $termsImported, 'classes' => $classesUpserted];
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
        $normalizedName = Str::withoutCourseTitlePunctuation($name);

        $course = Course::query()
            ->where('term', $term)
            ->where(function ($query) use ($normalizedName) {
                $query->where('name', $normalizedName)
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '（', ''), '）', ''), '(', ''), ')', ''), '：', ''), ':', ''), '～', ''), '~', ''), '—', ''), '－', ''), '-', ''), '–', ''), '　', ''), ' ', '') = ?",
                        [$normalizedName],
                    );
            })
            ->first();

        return $course ?? Course::query()->firstOrCreate([
            'name' => $name,
            'term' => $term,
        ]);
    }
}
