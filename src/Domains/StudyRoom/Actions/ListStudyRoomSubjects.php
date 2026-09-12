<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\Course;
use App\Models\StudentSchedule;
use Illuminate\Contracts\Database\Query\Builder;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSubjectViewModel;

/**
 * The courses in the viewer's own schedule for the current term, plus a
 * 其他 sentinel (`id: null`) so a student studying something outside their
 * course list can still say what.
 *
 * Deliberately goes via `course` (student_schedule_items.course_id, always
 * set), not `courseClass.course` — the latter silently drops any course
 * whose class hasn't been assigned yet.
 */
final readonly class ListStudyRoomSubjects
{
    public function __invoke(StudentSchedule $schedule): array
    {
        $term = (string) config('app.current_semester');

        $courses = $schedule->items()
            ->whereHas('course', fn (Builder $query) => $query->where('term', $term))
            ->with('course:id,name')
            ->get()
            ->pluck('course')
            ->filter()
            ->unique('id')
            ->values();

        $subjects = $courses
            ->map(fn (Course $course): StudyRoomSubjectViewModel => new StudyRoomSubjectViewModel(
                id: $course->id,
                name: $course->name,
            ))
            ->values()
            ->all();

        $subjects[] = new StudyRoomSubjectViewModel(id: null, name: '其他');

        return $subjects;
    }
}
