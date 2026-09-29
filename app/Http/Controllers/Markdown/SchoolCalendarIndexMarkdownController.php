<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\SchoolCalendar\Actions\ShowSchoolCalendarPage;
use NouTools\Domains\SchoolCalendar\DataTransferObjects\ShowSchoolCalendarData;

final class SchoolCalendarIndexMarkdownController extends Controller
{
    public function __invoke(ShowSchoolCalendarPage $showSchoolCalendarPage, ShowSchoolCalendarData $input): Response
    {
        return response()
            ->view('school-calendar.markdown.index', ['viewModel' => $showSchoolCalendarPage($input)])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
