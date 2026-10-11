<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\Support;

use App\Models\Course;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Collection;

/**
 * The distinct courses a schedule holds in one term, via its chosen classes.
 */
final class ScheduleTermCourses
{
    /**
     * @return Collection<int, Course>
     */
    public static function for(StudentSchedule $schedule, string $term): Collection
    {
        return Course::query()
            ->where('term', $term)
            ->whereIn('id', $schedule->items()
                ->join('course_classes', 'course_classes.id', '=', 'student_schedule_items.course_class_id')
                ->select('course_classes.course_id'))
            ->orderBy('id')
            ->get(['id', 'name', 'credits']);
    }
}
