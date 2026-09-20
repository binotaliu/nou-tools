<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Exceptions\CannotPauseTimerException;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;

/**
 * Pauses a running Focus-phase timer. Like `ChangeStudyActivity`, it closes
 * out the elapsed segment through `RecordStudySession` and leaves
 * `timer_started_at`/`timer_ends_at` untouched — the progress bar is
 * derived from those two, so it stays frozen at where it was (the client
 * measures it up to `paused_at`) instead of jumping back or running on.
 * `ResumeStudyTimer` later shifts both forward by however long the pause
 * lasted and starts the next segment.
 *
 * While `paused_at` is set `RecordStudySession` is a no-op, so leaving the
 * seat, stopping the timer or being idle-released never records the pause
 * itself as study time.
 */
final readonly class PauseStudyTimer
{
    public function __construct(
        private RecordStudySession $recordStudySession,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer): StudyRoomSeat
    {
        $seat = DB::transaction(function () use ($viewer): StudyRoomSeat {
            $seat = StudyRoomSeat::query()
                ->where('student_schedule_id', $viewer->id)
                ->first();

            if ($seat === null) {
                throw new NoSeatHeldException;
            }

            if (
                $seat->timer_mode === null
                || $seat->timer_phase !== StudyTimerPhase::Focus
                || $seat->paused_at !== null
            ) {
                throw new CannotPauseTimerException;
            }

            $now = Date::now();

            $seat->last_seen_at = $now;
            $seat->saveOrFail();

            ($this->recordStudySession)($seat);

            $seat->paused_at = $now;
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.paused', $seat);

        return $seat;
    }
}
