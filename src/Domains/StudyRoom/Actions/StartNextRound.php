<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\Exceptions\NotOnBreakException;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;

/**
 * Moves a pomodoro from its break into the next focus round. Allowed at
 * any point during the break, not only once it has run out — cutting a
 * break short is the student's call. The activity and subject carry over
 * unchanged; only the round number advances, so the break after it can
 * again be the short or the long one.
 */
final readonly class StartNextRound
{
    public function __construct(
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

            $isOnPomodoroBreak = $seat->timer_mode === StudyTimerMode::Pomodoro
                && $seat->timer_phase === StudyTimerPhase::Break
                && $seat->timer_round !== null;

            if (! $isOnPomodoroBreak) {
                throw new NotOnBreakException;
            }

            $cycle = PomodoroCycle::forProfile($seat->schedule?->studyRoomProfile);
            $now = Date::now();

            $seat->last_seen_at = $now;
            $seat->timer_phase = StudyTimerPhase::Focus;
            $seat->timer_round = $seat->timer_round + 1;
            $seat->timer_started_at = $now;
            $seat->activity_started_at = $now;
            $seat->timer_ends_at = $now->addMinutes($cycle->focusMinutes);
            $seat->timer_end_notified_at = null;
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.started', $seat);

        return $seat;
    }
}
