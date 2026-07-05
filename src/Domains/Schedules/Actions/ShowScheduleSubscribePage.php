<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ViewModels\ScheduleSubscribePageViewModel;

final class ShowScheduleSubscribePage
{
    public function __invoke(StudentSchedule $schedule): ScheduleSubscribePageViewModel
    {
        return ScheduleSubscribePageViewModel::fromModel($schedule);
    }
}
