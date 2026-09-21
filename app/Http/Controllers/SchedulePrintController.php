<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PrintWeekStart;
use App\Models\StudentSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\BuildSchedulePrintPage;

final class SchedulePrintController extends Controller
{
    public function show(StudentSchedule $schedule, Request $request, BuildSchedulePrintPage $buildSchedulePrintPage): View
    {
        return view('schedule.print', [
            'page' => $buildSchedulePrintPage($schedule, $request->query('term'), PrintWeekStart::fromQuery($request->query('week_start'))),
            'inlineAssets' => false,
        ]);
    }
}
