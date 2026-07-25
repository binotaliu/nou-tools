<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\Course;
use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorPageViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class BuildScheduleEditorPage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
    ) {}

    public function __invoke(Request $request, ?StudentSchedule $schedule = null): ScheduleEditorPageViewModel
    {
        if ($schedule) {
            $schedule->load(['items.courseClass.course']);
        }

        $currentSemester = config('app.current_semester');
        $courses = ScheduleEditorCourseViewModel::collect(
            Course::query()
                ->where('term', $currentSemester)
                ->whereHas('classes')
                ->with(['classes' => function ($query) {
                    $query->orderBy('type');
                }])
                ->orderBy('name')
                ->get()
                ->map(fn (Course $course) => ScheduleEditorCourseViewModel::fromModel($course)),
            DataCollection::class,
        );

        $previousSchedule = null;

        if (! $schedule && ! $request->boolean('new')) {
            $previousSchedule = ($this->readStudentScheduleCookie)($request);
        }

        return new ScheduleEditorPageViewModel(
            courses: $courses,
            currentSemester: $currentSemester,
            schedule: $schedule,
            previousSchedule: $previousSchedule,
        );
    }
}
