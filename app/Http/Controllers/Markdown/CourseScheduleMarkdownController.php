<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NouTools\Domains\Courses\Actions\BuildCourseSchedulePage;

final class CourseScheduleMarkdownController extends Controller
{
    public function __invoke(Request $request, BuildCourseSchedulePage $buildCourseSchedulePage): Response
    {
        $term = $request->query('term');

        return response()
            ->view('course.markdown.schedule', [
                'page' => $buildCourseSchedulePage(is_string($term) ? $term : null),
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
