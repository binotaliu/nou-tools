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

/**
 * Starts the break after a finished focus timer. The break is manual by
 * product decision — there's no auto-advance from Focus to Break, so this
 * only succeeds once the focus timer has actually run out.
 *
 * Finalizes the focus session itself (idempotently — a no-op if a
 * heartbeat already finalized it) rather than assuming one already ran,
 * since a student can click 開始休息 before the next heartbeat lands.
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
                && $seat->timer_ends_at !== null
                && $now->greaterThanOrEqualTo($seat->timer_ends_at);

            if (! $hasFinishedFocusTimer) {
                throw new TimerNotFinishedException;
            }

            $seat->last_seen_at = $now;
            $seat->saveOrFail();

            ($this->recordStudySession)($seat);

            $breakMinutes = (int) config('study-room.timer.pomodoro.break_minutes');

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
