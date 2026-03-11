<?php

namespace NouTools\Domains\Courses\Actions;

use App\Models\Course;
use App\Models\PreviousExam;
use Illuminate\Http\Request;
use NouTools\Domains\Courses\ViewModels\CourseShowPageViewModel;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

final readonly class ShowCoursePage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
    ) {}

    public function __invoke(Course $course, Request $request): CourseShowPageViewModel
    {
        $course->load([
            'classes' => function ($query) {
                $query->with('schedules')->orderBy('type');
            },
            'textbook',
        ]);

        $previousSchedule = ($this->readStudentScheduleCookie)($request);
        $previousExams = collect();

        if ($previousSchedule) {
            $previousExams = PreviousExam::query()
                ->where('course_name', $course->name)
                ->orderByDesc('term')
                ->get();
        }

        return new CourseShowPageViewModel(
            course: $course,
            previousSchedule: $previousSchedule,
            previousExams: $previousExams,
        );
    }
}
