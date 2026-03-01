<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\PreviousExam;

class CourseController extends Controller
{
    public function show(Course $course): \Illuminate\View\View
    {
        $course->load([
            'classes' => function ($query) {
                $query->with('schedules')->orderBy('type');
            },
            'textbook',
        ]);

        $previousSchedule = request()->studentScheduleFromCookie();

        // Load previous exam data if schedule cookie exists
        $previousExams = collect();
        if ($previousSchedule) {
            $previousExams = PreviousExam::query()
                ->where('course_name', $course->name)
                ->orderByDesc('term')
                ->get();
        }

        return view('course.show', [
            'course' => $course,
            'previousSchedule' => $previousSchedule,
            'previousExams' => $previousExams,
        ]);
    }
}
