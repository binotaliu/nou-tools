<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\Course;
use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorPageViewModel;

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
        $courses = Course::query()
            ->where('term', $currentSemester)
            ->whereHas('classes')
            ->with(['classes' => function ($query) {
                $query->orderBy('type');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'term' => $course->term,
                    'classes' => $course->classes->map(function ($class) {
                        return [
                            'id' => $class->id,
                            'code' => $class->code,
                            'type' => $class->type->value,
                            'type_label' => $class->type->label(),
                            'start_time' => $class->start_time,
                            'end_time' => $class->end_time,
                            'teacher_name' => $class->teacher_name,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

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
