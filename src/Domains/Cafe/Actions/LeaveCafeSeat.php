<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudyRoomSeat;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\ReleaseSeat;

/**
 * Gives up the viewer's café seat: records any running focus session, clears
 * the timer and throws away the 內用 drinks (`ReleaseSeat` does the last
 * for a café seat). Idempotent, and it never touches a study-room seat.
 */
final readonly class LeaveCafeSeat
{
    public function __construct(
        private ReleaseSeat $releaseSeat,
        private BroadcastCafeChange $broadcastCafeChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer): void
    {
        $holdsCafeSeat = StudyRoomSeat::query()
            ->inRoom(StudyRoomVenue::Cafe)
            ->where('student_schedule_id', $viewer->id)
            ->exists();

        if (! $holdsCafeSeat) {
            return;
        }

        $seat = ($this->releaseSeat)($viewer);

        if ($seat !== null) {
            ($this->broadcastCafeChange)('seat.left', $seat);
        }
    }
}
