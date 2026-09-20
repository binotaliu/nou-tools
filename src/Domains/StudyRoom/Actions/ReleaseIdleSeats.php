<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Releases every seat whose `last_seen_at` is older than
 * `study-room.heartbeat.idle_release_seconds`, recording any running
 * session first. Deliberately does not bump `last_seen_at` before
 * recording — unlike an interactive release (`ReleaseSeat`), the whole
 * point here is to credit focus time only up to the seat's last real
 * heartbeat, not to "now".
 */
final readonly class ReleaseIdleSeats
{
    public function __construct(
        private RecordStudySession $recordStudySession,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(): int
    {
        $idleReleaseSeconds = (int) config('study-room.heartbeat.idle_release_seconds');
        $cutoff = Date::now()->subSeconds($idleReleaseSeconds);

        $idleSeats = StudyRoomSeat::query()
            ->whereNotNull('student_schedule_id')
            ->where('last_seen_at', '<', $cutoff)
            ->get();

        foreach ($idleSeats as $seat) {
            DB::transaction(function () use ($seat): void {
                ($this->recordStudySession)($seat);

                $seat->student_schedule_id = null;
                $seat->occupied_at = null;
                $seat->last_seen_at = null;
                $seat->activity_verb = null;
                $seat->subject_course_id = null;
                $seat->subject_label = null;
                $seat->timer_mode = null;
                $seat->timer_phase = null;
                $seat->timer_round = null;
                $seat->timer_started_at = null;
                $seat->timer_ends_at = null;
                $seat->paused_at = null;
                $seat->saveOrFail();
            });

            ($this->broadcastStudyRoomChange)('seat.idle-released', $seat);
        }

        return $idleSeats->count();
    }
}
