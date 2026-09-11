<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;

/**
 * Stops the viewer's running timer: records whatever focus session is due
 * (a no-op when the timer is in the Break phase) and clears the timer
 * columns, keeping the seat itself occupied.
 */
final readonly class StopStudyTimer
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

            $seat->last_seen_at = Date::now();
            $seat->saveOrFail();

            ($this->recordStudySession)($seat);

            $seat->timer_mode = null;
            $seat->timer_phase = null;
            $seat->timer_started_at = null;
            $seat->timer_ends_at = null;
            $seat->saveOrFail();

            return $seat;
        });

        ($this->broadcastStudyRoomChange)('timer.stopped', $seat);

        return $seat;
    }
}
