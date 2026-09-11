<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Bumps `last_seen_at` on the viewer's held seat, keeping it alive for
 * `ReleaseIdleSeats`. If a Focus-phase timer's `timer_ends_at` has already
 * passed, finalizes it into a `study_room_sessions` row and nulls
 * `timer_started_at` — the seat is deliberately left otherwise as-is
 * (still Focus phase, `timer_ends_at` in the past) so the client can show
 * "finished, press 開始休息" rather than auto-advancing to a break. Nulling
 * `timer_started_at` is what makes this idempotent: `RecordStudySession`
 * is a no-op once it's null, so a later heartbeat (or a manual 開始休息)
 * never double-records the same finished timer.
 */
final readonly class RecordHeartbeat
{
    public function __construct(
        private RecordStudySession $recordStudySession,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer): bool
    {
        [$seat, $finalized] = DB::transaction(function () use ($viewer): array {
            $seat = StudyRoomSeat::query()
                ->where('student_schedule_id', $viewer->id)
                ->first();

            if ($seat === null) {
                return [null, false];
            }

            $now = Date::now();
            $seat->last_seen_at = $now;
            $seat->saveOrFail();

            $shouldFinalize = $seat->timer_phase === StudyTimerPhase::Focus
                && $seat->timer_started_at !== null
                && $seat->timer_ends_at !== null
                && $now->greaterThanOrEqualTo($seat->timer_ends_at);

            if ($shouldFinalize) {
                ($this->recordStudySession)($seat);
                $seat->timer_started_at = null;
                $seat->saveOrFail();
            }

            return [$seat, $shouldFinalize];
        });

        if ($finalized) {
            ($this->broadcastStudyRoomChange)('timer.finished', $seat);
        }

        return $seat !== null;
    }
}
