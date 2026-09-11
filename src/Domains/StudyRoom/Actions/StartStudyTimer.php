<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\StartStudyTimerData;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;

/**
 * Starts a Focus-phase timer on the viewer's held seat. A pomodoro always
 * runs `timer.pomodoro.focus_minutes`; a custom timer runs the validated
 * `minutes` from the request. 其他 (no course) is recorded with a fixed,
 * non-user-supplied label — deliberately not free text, since it's a
 * second publicly-visible field we've chosen not to have to moderate.
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

            $minutes = $data->mode === StudyTimerMode::Pomodoro
                ? (int) config('study-room.timer.pomodoro.focus_minutes')
                : (int) $data->minutes;

            $now = Date::now();

            $seat->last_seen_at = $now;
            $seat->activity_verb = $data->verb;
            $seat->subject_course_id = $data->subjectCourseId;
            $seat->subject_label = $data->subjectCourseId === null ? '其他' : null;
            $seat->timer_mode = $data->mode;
            $seat->timer_phase = StudyTimerPhase::Focus;
            $seat->timer_started_at = $now;
            $seat->timer_ends_at = $now->addMinutes($minutes);
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.started', $seat);

        return $seat;
    }
}
