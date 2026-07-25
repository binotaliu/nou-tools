<?php

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NouTools\Domains\Courses\Actions\ShowCoursePage;

class CourseShowMarkdownController extends Controller
{
    public function __invoke(Course $course, Request $request, ShowCoursePage $showCoursePage): Response
    {
        $page = $showCoursePage($course, $request);

        return response()
            ->view('course.markdown.show', [
                'course' => $page->course,
                'inPersonClassType' => $page->inPersonClassType,
                'media' => $page->media,
                'multimediaUrl' => $page->multimediaUrl,
                'previousSchedule' => $page->previousSchedule,
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
