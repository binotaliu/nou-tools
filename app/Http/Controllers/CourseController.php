<?php

namespace App\Http\Controllers;

use App\Models\Course;

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

        return view('course.show', [
            'course' => $course,
            'previousSchedule' => $previousSchedule,
        ]);
    }
}
