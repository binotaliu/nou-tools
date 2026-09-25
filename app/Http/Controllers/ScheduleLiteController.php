<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\ShowSchedulePage;

final class ScheduleLiteController extends Controller
{
    public function __invoke(StudentSchedule $schedule, Request $request, ShowSchedulePage $showSchedulePage): View
    {
        return view('schedule.lite', [
            'viewModel' => $showSchedulePage($schedule, $request->query('term')),
        ]);
    }
}
