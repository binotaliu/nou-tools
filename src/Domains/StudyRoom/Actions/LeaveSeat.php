<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Releases the seat currently held by the viewer, if any. Idempotent —
 * calling it when the viewer holds no seat is a no-op, not an error, since
 * both an explicit "leave" click and a stale/duplicate request should
 * behave the same way.
 */
final readonly class LeaveSeat
{
    public function __construct(
        private ReleaseSeat $releaseSeat,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer): void
    {
        $seat = ($this->releaseSeat)($viewer);

        if ($seat !== null) {
            ($this->broadcastStudyRoomChange)('seat.left', $seat);
        }
    }
}
