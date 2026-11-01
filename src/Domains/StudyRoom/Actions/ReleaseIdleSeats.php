<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;

/**
 * Releases every seat of a room whose `last_seen_at` is older than that
 * room's `heartbeat.idle_release_seconds`, recording any running
 * session first. Deliberately does not bump `last_seen_at` before
 * recording — unlike an interactive release (`ReleaseSeat`), the whole
 * point here is to credit focus time only up to the seat's last real
 * heartbeat, not to "now".
 */
final readonly class ReleaseIdleSeats
{
    public function __construct(
        private RecordStudySession $recordStudySession,
        private ReleaseSeats $releaseSeats,
    ) {}

    public function __invoke(StudyRoomVenue $room = StudyRoomVenue::Study): int
    {
        $idleReleaseSeconds = (int) config($room === StudyRoomVenue::Cafe ? 'cafe.heartbeat.idle_release_seconds' : 'study-room.heartbeat.idle_release_seconds');
        $cutoff = Date::now()->subSeconds($idleReleaseSeconds);

        $idleSeats = StudyRoomSeat::query()
            ->inRoom($room)
            ->whereNotNull('student_schedule_id')
            ->where('last_seen_at', '<', $cutoff)
            ->get();

        return ($this->releaseSeats)($idleSeats, 'seat.idle-released');
    }
}
