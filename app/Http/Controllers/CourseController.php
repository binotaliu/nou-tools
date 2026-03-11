<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use NouTools\Domains\Courses\Actions\ShowCoursePage;

class CourseController extends Controller
{
    public function show(Course $course, Request $request, ShowCoursePage $showCoursePage): \Illuminate\View\View
    {
        $page = $showCoursePage($course, $request);

        return view('course.show', [
            'course' => $page->course,
            'previousSchedule' => $page->previousSchedule,
            'previousExams' => $page->previousExams,
        ]);
    }
}
