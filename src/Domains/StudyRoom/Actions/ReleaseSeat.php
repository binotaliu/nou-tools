<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Clears occupancy and all activity/timer columns on the seat currently
 * held by the viewer, if any, finalizing any running focus timer into a
 * `study_room_sessions` row first. Deliberately does not broadcast: it's
 * used both standalone (via `LeaveSeat`) and as a step inside `TakeSeat`'s
 * own transaction when a seat move frees the viewer's previous seat, and
 * broadcasting from here would fire before the caller's outer transaction
 * (if any) has actually committed. Callers broadcast themselves once they
 * know their own transaction succeeded.
 *
 * Bumps `last_seen_at` to now before recording — leaving (or moving away
 * from) a seat is itself live, interactive evidence the student is present,
 * so the recorded session isn't clamped to a stale heartbeat. Idle-based
 * release (`ReleaseIdleSeats`) intentionally does not go through this
 * action, since it must record only up to the seat's last real heartbeat.
 */
final readonly class ReleaseSeat
{
    public function __construct(
        private RecordStudySession $recordStudySession,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer): ?StudyRoomSeat
    {
        return DB::transaction(function () use ($viewer): ?StudyRoomSeat {
            $seat = StudyRoomSeat::query()
                ->where('student_schedule_id', $viewer->id)
                ->first();

            if ($seat === null) {
                return null;
            }

            $seat->last_seen_at = Date::now();
            $seat->saveOrFail();

            ($this->recordStudySession)($seat);

            $seat->student_schedule_id = null;
            $seat->occupied_at = null;
            $seat->last_seen_at = null;
            $seat->activity_verb = null;
            $seat->subject_course_id = null;
            $seat->subject_label = null;
            $seat->timer_mode = null;
            $seat->timer_phase = null;
            $seat->timer_started_at = null;
            $seat->timer_ends_at = null;
            $seat->saveOrFail();

            return $seat;
        });
    }
}
