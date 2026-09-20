<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\Exceptions\TimerNotPausedException;

/**
 * Resumes a paused timer. Shifts `timer_started_at` and `timer_ends_at`
 * forward by the paused duration, so the progress bar continues from where
 * it froze and the countdown (or count-up) picks up with exactly the time
 * that was left. `activity_started_at` restarts at now, so the next
 * `RecordStudySession` measures only the new segment against what remains
 * of the plan.
 */
final readonly class ResumeStudyTimer
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

            if ($seat->paused_at === null) {
                throw new TimerNotPausedException;
            }

            $now = Date::now();
            $pausedSeconds = max(0, $now->getTimestamp() - $seat->paused_at->getTimestamp());

            $seat->last_seen_at = $now;
            $seat->timer_started_at = $seat->timer_started_at?->addSeconds($pausedSeconds);
            $seat->timer_ends_at = $seat->timer_ends_at?->addSeconds($pausedSeconds);
            $seat->activity_started_at = $now;
            $seat->paused_at = null;
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.resumed', $seat);

        return $seat;
    }
}
