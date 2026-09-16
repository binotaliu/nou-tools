<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Schedules\Actions\ShowScheduleSubscribePage;

final class ScheduleSubscribeController extends Controller
{
    public function __invoke(StudentSchedule $schedule, ShowScheduleSubscribePage $showScheduleSubscribePage): Response
    {
        return Inertia::render('Schedule/Subscribe', [
            'viewModel' => $showScheduleSubscribePage($schedule),
        ]);
    }
}
