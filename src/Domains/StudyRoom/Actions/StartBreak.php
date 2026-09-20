<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\Exceptions\TimerNotFinishedException;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;

/**
 * Starts the break after a finished focus timer. The break is manual by
 * product decision — there's no auto-advance from Focus to Break, so this
 * only succeeds once the focus timer has actually run out. Its length
 * comes from the student's own cycle: the short break after most rounds,
 * the long one after the last round of a cycle. A finished custom timer
 * gets the short break (it has no round to be the last of).
 *
 * Finalizes the focus session itself — heartbeats never do, so this is the
 * only place a normally completed focus round (including any overtime run
 * past its planned end) gets recorded before the seat moves to Break.
 */
final readonly class StartBreak
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

            $now = Date::now();

            $hasFinishedFocusTimer = $seat->timer_phase === StudyTimerPhase::Focus
                && $seat->paused_at === null
                && $seat->timer_ends_at !== null
                && $now->greaterThanOrEqualTo($seat->timer_ends_at);

            if (! $hasFinishedFocusTimer) {
                throw new TimerNotFinishedException;
            }

            $seat->last_seen_at = $now;
            $seat->saveOrFail();

            ($this->recordStudySession)($seat);

            $cycle = PomodoroCycle::forProfile($seat->schedule?->studyRoomProfile);
            $breakMinutes = $seat->timer_round === null
                ? $cycle->shortBreakMinutes
                : $cycle->breakMinutesAfterRound($seat->timer_round);

            $seat->timer_phase = StudyTimerPhase::Break;
            $seat->timer_started_at = $now;
            $seat->timer_ends_at = $now->addMinutes($breakMinutes);
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.break', $seat);

        return $seat;
    }
}
