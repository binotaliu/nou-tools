<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\DB;

/**
 * Undoes `FillFloorWithTestStudents`: frees every seat held by a test
 * student and deletes their throwaway schedules (profiles cascade).
 * Nothing is recorded as a study session — these were never real people.
 */
final readonly class ReleaseTestStudents
{
    public function __construct(
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    /**
     * @return int number of seats released
     */
    public function __invoke(): int
    {
        /** @var array<int, StudyRoomSeat> $releasedSeats */
        $releasedSeats = DB::transaction(function (): array {
            $schedules = StudentSchedule::query()
                ->where('name', 'like', FillFloorWithTestStudents::SCHEDULE_NAME_PREFIX.'%')
                ->get();

            $released = [];

            foreach (StudyRoomSeat::query()->whereIn('student_schedule_id', $schedules->modelKeys())->get() as $seat) {
                $seat->student_schedule_id = null;
                $seat->occupied_at = null;
                $seat->last_seen_at = null;
                $seat->activity_verb = null;
                $seat->subject_course_id = null;
                $seat->subject_label = null;
                $seat->timer_mode = null;
                $seat->timer_phase = null;
                $seat->timer_round = null;
                $seat->timer_started_at = null;
                $seat->timer_ends_at = null;
                $seat->no_timer_since = null;
                $seat->saveOrFail();
                $released[] = $seat;
            }

            foreach ($schedules as $schedule) {
                $schedule->delete();
            }

            return $released;
        });

        foreach ($releasedSeats as $seat) {
            ($this->broadcastStudyRoomChange)('seat.left', $seat);
        }

        return count($releasedSeats);
    }
}
