<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ViewModels\ScheduleViewModel;

final class ShowSchedulePage
{
    public function __invoke(StudentSchedule $schedule): ScheduleViewModel
    {
        $schedule->load(['items.courseClass.course', 'items.courseClass.schedules']);

        return ScheduleViewModel::fromModel($schedule);
    }
}
