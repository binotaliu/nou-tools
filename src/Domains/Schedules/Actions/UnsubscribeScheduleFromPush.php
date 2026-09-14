<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\DataTransferObjects\PushUnsubscribeData;

final class UnsubscribeScheduleFromPush
{
    public function __invoke(StudentSchedule $schedule, PushUnsubscribeData $input): void
    {
        $schedule->deletePushSubscription($input->endpoint);
    }
}
