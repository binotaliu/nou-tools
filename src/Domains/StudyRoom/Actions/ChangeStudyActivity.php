<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\ChangeStudyActivityData;
use NouTools\Domains\StudyRoom\Exceptions\NoActiveTimerException;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;

/**
 * Switches the activity/subject on a running Focus-phase timer without
 * stopping it. Reuses `RecordStudySession` unmodified to close out the old
 * activity's segment (crediting elapsed time, including any overtime, to
 * whatever was running before), then resets `activity_started_at` to now
 * so the next `RecordStudySession` call correctly measures only the new
 * activity's segment. `timer_started_at`/`timer_ends_at`/phase/round are
 * deliberately left untouched — the countdown progress bar is anchored to
 * `timer_started_at`, and resetting it here would make the bar jump back
 * to 0% even though nothing about the round's own plan changed.
 *
 * Only valid during Focus: there's nothing running to split during a break.
 */
final readonly class ChangeStudyActivity
{
    public function __construct(
        private RecordStudySession $recordStudySession,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer, ChangeStudyActivityData $data): StudyRoomSeat
    {
        $seat = DB::transaction(function () use ($viewer, $data): StudyRoomSeat {
            $seat = StudyRoomSeat::query()
                ->where('student_schedule_id', $viewer->id)
                ->first();

            if ($seat === null) {
                throw new NoSeatHeldException;
            }

            if ($seat->timer_mode === null || $seat->timer_phase !== StudyTimerPhase::Focus) {
                throw new NoActiveTimerException;
            }

            $now = Date::now();

            $seat->last_seen_at = $now;
            $seat->saveOrFail();

            ($this->recordStudySession)($seat);

            $seat->activity_started_at = $now;
            $seat->activity_verb = $data->verb;
            $seat->subject_course_id = $data->subjectCourseId;
            $seat->subject_label = $data->subjectCourseId === null ? '其他' : null;
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.activity_changed', $seat);

        return $seat;
    }
}
