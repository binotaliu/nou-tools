<?php

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\Response;
use NouTools\Domains\Schedules\Actions\GenerateScheduleCalendar;

class ScheduleCalendarController extends Controller
{
    public function __invoke(StudentSchedule $schedule, GenerateScheduleCalendar $generateScheduleCalendar): Response
    {
        $ics = $generateScheduleCalendar($schedule);

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="schedule.ics"');
    }
}
