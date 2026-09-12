<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Exceptions\AlreadySeatedException;
use NouTools\Domains\StudyRoom\Exceptions\FloorClosedException;
use NouTools\Domains\StudyRoom\Exceptions\SeatUnavailableException;

/**
 * Claims an empty seat for the viewer. Race-safe on SQLite (and everywhere
 * else): the actual claim is a conditional UPDATE — "set this seat's
 * student_schedule_id WHERE it is currently NULL" — so two concurrent
 * requests for the same seat can never both succeed. Whichever one loses
 * either sees 0 affected rows, or trips the `unique(student_schedule_id)`
 * index if it raced past the WHERE clause; both are treated the same way.
 *
 * Seat switching is disallowed: a viewer who already holds a seat must
 * leave it (`LeaveSeat`) before claiming another. This is checked inside
 * the same transaction as the claim, so a concurrent `LeaveSeat` can't
 * race a `TakeSeat` into leaving the viewer seated in two places at once.
 */
final readonly class TakeSeat
{
    public function __construct(
        private ResolveOpenFloorCount $resolveOpenFloorCount,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer, StudyRoomSeat $seat): StudyRoomSeat
    {
        $seat = DB::transaction(function () use ($viewer, $seat): StudyRoomSeat {
            if ($seat->floor > ($this->resolveOpenFloorCount)()) {
                throw new FloorClosedException;
            }

            $alreadySeated = StudyRoomSeat::query()
                ->where('student_schedule_id', $viewer->id)
                ->exists();

            if ($alreadySeated) {
                throw new AlreadySeatedException;
            }

            try {
                $affected = StudyRoomSeat::query()
                    ->where('id', $seat->id)
                    ->whereNull('student_schedule_id')
                    ->update([
                        'student_schedule_id' => $viewer->id,
                        'occupied_at' => Date::now(),
                        'last_seen_at' => Date::now(),
                        'no_timer_since' => Date::now(),
                    ]);
            } catch (QueryException $exception) {
                if ($exception->getCode() === '23000') {
                    throw new SeatUnavailableException;
                }

                throw $exception;
            }

            if ($affected === 0) {
                throw new SeatUnavailableException;
            }

            return $seat->fresh();
        });

        ($this->broadcastStudyRoomChange)('seat.taken', $seat);

        return $seat;
    }
}
