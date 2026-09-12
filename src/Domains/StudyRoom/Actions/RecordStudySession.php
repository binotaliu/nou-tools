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
 * `min(now, last_seen_at) - timer_started_at`, clamped to
 * `[0, timer.max_session_seconds]`. Clamping to `last_seen_at` matters for
 * the idle/heartbeat path — a browser that dies mid-pomodoro is credited
 * only up to its last heartbeat, never the full planned duration. Unlike
 * `last_seen_at`, `timer_ends_at` no longer caps the result: a countdown
 * keeps ticking (and being credited) past its planned end until the
 * student acts, and the portion beyond the plan is broken out separately
 * as `overtime_seconds`. A count-up timer has no plan (`timer_ends_at` is
 * null) — its whole elapsed time is ordinary focus time, never overtime.
 */
final readonly class RecordStudySession
{
    public function __invoke(StudyRoomSeat $seat): ?StudyRoomSession
    {
        if ($seat->student_schedule_id === null) {
            return null;
        }

        if ($seat->timer_phase !== StudyTimerPhase::Focus || $seat->timer_started_at === null) {
            return null;
        }

        $now = Date::now();
        $lastSeenAt = $seat->last_seen_at ?? $now;
        $effectiveEnd = $now->min($lastSeenAt);

        $maxSessionSeconds = (int) config('study-room.timer.max_session_seconds');
        $focusSeconds = max(0, $effectiveEnd->getTimestamp() - $seat->timer_started_at->getTimestamp());
        $focusSeconds = min($focusSeconds, $maxSessionSeconds);

        if ($seat->timer_ends_at === null) {
            $plannedSeconds = $focusSeconds;
            $wasCompleted = true;
        } else {
            $plannedSeconds = $seat->timer_ends_at->getTimestamp() - $seat->timer_started_at->getTimestamp();
            $wasCompleted = $focusSeconds >= $plannedSeconds;
        }

        $overtimeSeconds = max(0, $focusSeconds - $plannedSeconds);

        $session = new StudyRoomSession;
        $session->student_schedule_id = $seat->student_schedule_id;
        $session->subject_course_id = $seat->subject_course_id;
        $session->subject_label = $seat->subject_label;
        $session->activity_verb = $seat->activity_verb;
        $session->timer_mode = $seat->timer_mode;
        $session->started_at = $seat->timer_started_at;
        $session->ended_at = $effectiveEnd;
        $session->focus_seconds = $focusSeconds;
        $session->overtime_seconds = $overtimeSeconds;
        $session->was_completed = $wasCompleted;
        $session->saveOrFail();

        return $session;
    }
}
