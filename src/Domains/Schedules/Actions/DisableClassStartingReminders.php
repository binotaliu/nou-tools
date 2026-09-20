<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;

/**
 * Only clears the opt-in: the browser's push subscription is shared with the
 * study room's timer-end notification, so it is left in place.
 */
final readonly class DisableClassStartingReminders
{
    public function __invoke(StudentSchedule $schedule): void
    {
        $schedule->notify_on_class_start = false;
        $schedule->saveOrFail();
    }
}
