<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeSeat;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Gives up the viewer's café seat. Idempotent.
 */
final readonly class LeaveCafeSeat
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    public function __invoke(StudentScheduleCookie $viewer): void
    {
        $seat = CafeSeat::query()->where('student_schedule_id', $viewer->id)->first();

        if ($seat === null) {
            return;
        }

        $seat->student_schedule_id = null;
        $seat->occupied_at = null;
        $seat->last_seen_at = null;
        $seat->saveOrFail();

        ($this->broadcastCafeChange)('seat.left', $seat);
    }
}
