<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\Courses\Actions\ShowCoursePage;

class CourseController extends Controller
{
    public function show(Course $course, Request $request, ShowCoursePage $showCoursePage): View
    {
        $page = $showCoursePage($course, $request);

        return view('course.show', [
            'course' => $page->course,
            'previousSchedule' => $page->previousSchedule,
            'previousExams' => $page->previousExams,
        ]);
    }
}
