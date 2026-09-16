<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Courses\Actions\ShowCoursePage;

final class CourseController extends Controller
{
    public function show(Course $course, Request $request, ShowCoursePage $showCoursePage): Response
    {
        return Inertia::render('Courses/Show', [
            'viewModel' => $showCoursePage($course, $request),
            // Used as the "back" link's target when there's no saved schedule to
            // link back to (mirrors the Blade view's `url()->previous()` fallback).
            'fallbackBackUrl' => url()->previous(),
        ]);
    }
}
