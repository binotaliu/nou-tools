<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\View\View;
use NouTools\Domains\Schedules\Actions\ShowScheduleSubscribePage;

final class ScheduleSubscribeController extends Controller
{
    public function __invoke(StudentSchedule $schedule, ShowScheduleSubscribePage $showScheduleSubscribePage): View
    {
        return view('schedule.subscribe', [
            'viewModel' => $showScheduleSubscribePage($schedule),
        ]);
    }
}
