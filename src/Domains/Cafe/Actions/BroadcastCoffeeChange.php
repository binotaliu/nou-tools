<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\StudyRoomSeat;
use NouTools\Domains\StudyRoom\Actions\BroadcastSeatChange;

/**
 * Tells whichever room the student is sitting in that their cup changed. The
 * café event name is `$cafeType`; the study room sees a plain `seat.coffee`.
 * No seat held, nobody to tell.
 */
final readonly class BroadcastCoffeeChange
{
    public function __construct(private BroadcastSeatChange $broadcastSeatChange) {}

    public function __invoke(int $studentScheduleId, string $cafeType): void
    {
        $seat = StudyRoomSeat::query()->where('student_schedule_id', $studentScheduleId)->first();

        if ($seat === null) {
            return;
        }

        ($this->broadcastSeatChange)($seat->isCafe() ? $cafeType : 'seat.coffee', $seat);
    }
}
