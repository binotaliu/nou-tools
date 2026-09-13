<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\StartStudyTimerData;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;

/**
 * Starts a Focus-phase timer on the viewer's held seat. A pomodoro starts
 * round 1 of the student's own cycle (saving the cycle they sent as their
 * preference first, so it's what `StartBreak` and `StartNextRound` follow);
 * a 倒數 (custom) timer runs the validated `minutes` from the request with
 * no round at all; a 正數 (count-up) timer has no planned duration at all —
 * `timer_ends_at` stays null, so it only ever ends when the student stops
 * it. 其他 (no course) is recorded with a fixed, non-user-supplied label —
 * deliberately not free text, since it's a second publicly-visible field
 * we've chosen not to have to moderate.
 */
final readonly class StartStudyTimer
{
    public function __construct(
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer, StartStudyTimerData $data): StudyRoomSeat
    {
        $seat = DB::transaction(function () use ($viewer, $data): StudyRoomSeat {
            $seat = StudyRoomSeat::query()
                ->where('student_schedule_id', $viewer->id)
                ->first();

            if ($seat === null) {
                throw new NoSeatHeldException;
            }

            $isPomodoro = $data->mode === StudyTimerMode::Pomodoro;

            $minutes = match (true) {
                $isPomodoro => $this->resolveCycle($viewer, $data)->focusMinutes,
                $data->mode === StudyTimerMode::Custom => (int) $data->minutes,
                default => null, // CountUp: no planned duration
            };

            $now = Date::now();

            $seat->last_seen_at = $now;
            $seat->activity_verb = $data->verb;
            $seat->subject_course_id = $data->subjectCourseId;
            $seat->subject_label = $data->subjectCourseId === null ? '其他' : null;
            $seat->timer_mode = $data->mode;
            $seat->timer_phase = StudyTimerPhase::Focus;
            $seat->timer_round = $isPomodoro ? 1 : null;
            $seat->timer_started_at = $now;
            $seat->activity_started_at = $now;
            $seat->timer_ends_at = $minutes === null ? null : $now->addMinutes($minutes);
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.started', $seat);

        return $seat;
    }

    /**
     * Saves the cycle from the request onto the student's profile when one
     * was sent, and returns the cycle this timer should run on either way.
     */
    private function resolveCycle(StudentScheduleCookie $viewer, StartStudyTimerData $data): PomodoroCycle
    {
        $profile = StudyRoomProfile::query()
            ->where('student_schedule_id', $viewer->id)
            ->first();

        if (! $data->hasPomodoroCycle()) {
            return PomodoroCycle::forProfile($profile);
        }

        $cycle = $data->pomodoroCycle();

        if ($profile !== null) {
            $profile->pomodoro_focus_minutes = $cycle->focusMinutes;
            $profile->pomodoro_short_break_minutes = $cycle->shortBreakMinutes;
            $profile->pomodoro_long_break_minutes = $cycle->longBreakMinutes;
            $profile->pomodoro_rounds_per_cycle = $cycle->roundsPerCycle;
            $profile->saveOrFail();
        }

        return $cycle;
    }
}
