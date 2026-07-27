<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\Courses\Actions\BuildCourseSchedulePage;

final class CourseScheduleController extends Controller
{
    public function __invoke(Request $request, BuildCourseSchedulePage $buildCourseSchedulePage): View
    {
        $term = $request->query('term');

        return view('course.schedule', [
            'page' => $buildCourseSchedulePage(is_string($term) ? $term : null),
        ]);
    }
}
