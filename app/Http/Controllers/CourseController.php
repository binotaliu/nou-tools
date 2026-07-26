<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\Courses\Actions\ShowCoursePage;

final class CourseController extends Controller
{
    public function show(Course $course, Request $request, ShowCoursePage $showCoursePage): View
    {
        return view('course.show', [
            'viewModel' => $showCoursePage($course, $request),
        ]);
    }
}
