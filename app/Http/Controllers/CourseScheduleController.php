<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Courses\Actions\BuildCourseSchedulePage;

final class CourseScheduleController extends Controller
{
    public function __invoke(Request $request, BuildCourseSchedulePage $buildCourseSchedulePage): Response
    {
        $term = $request->query('term');

        return Inertia::render('Courses/Schedule', [
            'viewModel' => $buildCourseSchedulePage(is_string($term) ? $term : null),
        ]);
    }
}
