<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Program;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Stores the 專班 parsed by ParseNouProgramCourses for a term.
 *
 * A 專班's class is the 專班 itself: `course_classes.program_id` points at it
 * and `code` is the 專班's name, so (course, code, type) stays unique. Courses
 * are matched by (name, term) so one that 一般生 also take shares its Course
 * (exams, textbook, course map); a Course created here is flagged
 * `is_special_program_only` and stays out of the 一般生 lists.
 */
final readonly class ImportProgramCourses
{
    public function __construct(private ResolveClassDate $resolveClassDate) {}

    /**
     * @param  array<string, array<int, array{name: string, courses: array<int, array<string, mixed>>}>>  $programsByRegion  region slug => parsed programs
     * @param  bool  $removeStale  drop 專班 and classes of the term that were not in this import; only pass true when every region page was fetched
     */
    public function __invoke(string $term, array $programsByRegion, bool $removeStale = false): void
    {
        DB::transaction(function () use ($term, $programsByRegion, $removeStale): void {
            $position = 0;
            $seenClassIds = [];
            $seenProgramIds = [];

            foreach ($programsByRegion as $region => $programs) {
                foreach ($programs as $programData) {
                    $program = Program::query()->updateOrCreate(
                        ['term' => $term, 'name' => $programData['name']],
                        ['region' => $region, 'position' => $position++],
                    );

                    $seenProgramIds[] = $program->id;

                    foreach ($programData['courses'] as $courseData) {
                        $class = $this->importCourse($term, $program, $courseData);
                        $seenClassIds[] = $class->id;
                    }
                }
            }

            if ($removeStale) {
                $this->removeStale($term, $seenProgramIds, $seenClassIds);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $courseData
     */
    private function importCourse(string $term, Program $program, array $courseData): CourseClass
    {
        $course = Course::query()->firstOrCreate(
            ['name' => $courseData['name'], 'term' => $term],
            ['is_special_program_only' => true],
        );

        $class = CourseClass::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'code' => strtoupper($program->name),
                'type' => CourseClassType::SpecialProgram,
            ],
            [
                'program_id' => $program->id,
                'is_tentative' => false,
                'start_time' => $courseData['start_time'],
                'end_time' => $courseData['end_time'],
                'teacher_name' => $courseData['teacher_name'],
                'link' => $courseData['link'],
            ],
        );

        $dates = [];

        foreach ($courseData['dates'] as $index => $dateString) {
            $date = ($this->resolveClassDate)($dateString, $term);

            if ($date === null) {
                continue;
            }

            $override = $courseData['schedule_time_overrides'][$index + 1] ?? null;
            $dates[] = $date->format('Y-m-d');

            DB::table('class_schedules')->upsert(
                [[
                    'class_id' => $class->id,
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $override['start_time'] ?? null,
                    'end_time' => $override['end_time'] ?? null,
                    'created_at' => Date::now(),
                    'updated_at' => Date::now(),
                ]],
                ['class_id', 'date'],
                ['start_time', 'end_time', 'updated_at'],
            );
        }

        DB::table('class_schedules')
            ->where('class_id', $class->id)
            ->whereNotIn('date', $dates)
            ->delete();

        return $class;
    }

    /**
     * Classes that students already put in a schedule are kept even when the
     * page no longer lists them: deleting one would cascade into their
     * schedule items.
     *
     * @param  array<int, int>  $seenProgramIds
     * @param  array<int, int>  $seenClassIds
     */
    private function removeStale(string $term, array $seenProgramIds, array $seenClassIds): void
    {
        $termProgramIds = Program::query()->where('term', $term)->pluck('id');

        CourseClass::query()
            ->whereIn('program_id', $termProgramIds)
            ->whereNotIn('id', $seenClassIds)
            ->whereNotIn('id', StudentScheduleItem::query()->select('course_class_id')->whereNotNull('course_class_id'))
            ->delete();

        Program::query()
            ->where('term', $term)
            ->whereNotIn('id', $seenProgramIds)
            ->whereDoesntHave('classes')
            ->delete();

        Course::query()
            ->where('term', $term)
            ->where('is_special_program_only', true)
            ->whereDoesntHave('classes')
            ->whereNotIn('id', StudentScheduleItem::query()->select('course_id')->whereNotNull('course_id'))
            ->delete();
    }
}
