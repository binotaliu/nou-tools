<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\Date;

/**
 * Stops (or allows again) a schedule posting sticky notes.
 */
final readonly class BlockCafeSchedule
{
    public function __invoke(StudentSchedule $schedule, bool $blocked): void
    {
        $schedule->cafe_blocked_at = $blocked ? Date::now() : null;
        $schedule->saveOrFail();
    }
}
