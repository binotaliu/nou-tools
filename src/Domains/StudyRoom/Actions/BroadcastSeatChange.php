<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use NouTools\Domains\Cafe\Actions\BroadcastCafeChange;

/**
 * Tells the room a seat belongs to about a change: the café has its own
 * channel and state, the study room its own.
 */
final readonly class BroadcastSeatChange
{
    public function __construct(
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
        private BroadcastCafeChange $broadcastCafeChange,
    ) {}

    public function __invoke(string $type, StudyRoomSeat $seat): void
    {
        if ($seat->isCafe()) {
            ($this->broadcastCafeChange)($type, $seat);

            return;
        }

        ($this->broadcastStudyRoomChange)($type, $seat);
    }
}
