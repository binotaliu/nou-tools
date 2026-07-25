<?php

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NouTools\Domains\Schedules\Actions\ShowSchedulePage;

class ScheduleShowMarkdownController extends Controller
{
    public function __invoke(StudentSchedule $schedule, Request $request, ShowSchedulePage $showSchedulePage): Response
    {
        return response()
            ->view('schedule.markdown.show', [
                'viewModel' => $showSchedulePage($schedule, $request->query('term')),
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
