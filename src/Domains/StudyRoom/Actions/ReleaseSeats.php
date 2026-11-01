<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Cafe\Actions\TossDineInOrders;

final readonly class ReleaseSeats
{
    public function __construct(
        private RecordStudySession $recordStudySession,
        private BroadcastSeatChange $broadcastSeatChange,
        private TossDineInOrders $tossDineInOrders,
    ) {}

    /**
     * Gives each of the seats back (recording its session and, in the café,
     * throwing away the 內用 drinks) and tells its room.
     *
     * @param  Collection<int, StudyRoomSeat>  $seats
     */
    public function __invoke(Collection $seats, string $broadcastType): int
    {
        foreach ($seats as $seat) {
            $scheduleId = $seat->student_schedule_id;

            DB::transaction(function () use ($seat, $scheduleId): void {
                ($this->recordStudySession)($seat);

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
                $seat->paused_at = null;
                $seat->timer_end_notified_at = null;
                $seat->saveOrFail();

                if ($seat->isCafe()) {
                    ($this->tossDineInOrders)($scheduleId);
                }
            });

            ($this->broadcastSeatChange)($broadcastType, $seat);
        }

        return $seats->count();
    }
}
