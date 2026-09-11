<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;

/**
 * The single place that turns a seat's running timer state into a
 * `study_room_sessions` row. A no-op (returns null, no row written) when
 * the seat has no running Focus-phase timer — in particular a Break-phase
 * timer never produces a session.
 *
 * Focus seconds are computed server-side, never trusted from the client:
 * `min(now, timer_ends_at, last_seen_at) - timer_started_at`, clamped to
 * `[0, timer.max_session_seconds]`. Clamping to `last_seen_at` matters for
 * the idle/heartbeat path — a browser that dies mid-pomodoro is credited
 * only up to its last heartbeat, never the full planned duration.
 */
final readonly class RecordStudySession
{
    public function __invoke(StudyRoomSeat $seat): ?StudyRoomSession
    {
        if ($seat->student_schedule_id === null) {
            return null;
        }

        if ($seat->timer_phase !== StudyTimerPhase::Focus || $seat->timer_started_at === null || $seat->timer_ends_at === null) {
            return null;
        }

        $now = Date::now();
        $lastSeenAt = $seat->last_seen_at ?? $now;
        $effectiveEnd = $now->min($seat->timer_ends_at)->min($lastSeenAt);

        $maxSessionSeconds = (int) config('study-room.timer.max_session_seconds');
        $focusSeconds = max(0, $effectiveEnd->getTimestamp() - $seat->timer_started_at->getTimestamp());
        $focusSeconds = min($focusSeconds, $maxSessionSeconds);

        $plannedSeconds = $seat->timer_ends_at->getTimestamp() - $seat->timer_started_at->getTimestamp();
        $wasCompleted = $focusSeconds >= $plannedSeconds;

        $session = new StudyRoomSession;
        $session->student_schedule_id = $seat->student_schedule_id;
        $session->subject_course_id = $seat->subject_course_id;
        $session->subject_label = $seat->subject_label;
        $session->activity_verb = $seat->activity_verb;
        $session->timer_mode = $seat->timer_mode;
        $session->started_at = $seat->timer_started_at;
        $session->ended_at = $effectiveEnd;
        $session->focus_seconds = $focusSeconds;
        $session->was_completed = $wasCompleted;
        $session->saveOrFail();

        return $session;
    }
}
