<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Cafe\Exceptions\CafeAlreadySeatedException;
use NouTools\Domains\Cafe\Exceptions\CafeSeatUnavailableException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Claims an empty café seat (a `study_room_seats` row in the café room) with
 * one conditional UPDATE, the same race-safe approach as the study room's
 * `TakeSeat`. A student holds one seat across both rooms, and switching is not
 * allowed: leave first.
 */
final readonly class TakeCafeSeat
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    public function __invoke(StudentScheduleCookie $viewer, StudyRoomSeat $seat): StudyRoomSeat
    {
        $seat = DB::transaction(function () use ($viewer, $seat): StudyRoomSeat {
            $heldSeat = StudyRoomSeat::query()->where('student_schedule_id', $viewer->id)->first();

            if ($heldSeat !== null) {
                throw new CafeAlreadySeatedException(
                    $heldSeat->isCafe() ? '你已經坐在咖啡廳的座位上了。' : '你正坐在自習室，請先離座再到咖啡廳。',
                );
            }

            try {
                $affected = StudyRoomSeat::query()
                    ->where('id', $seat->id)
                    ->whereNull('student_schedule_id')
                    ->update([
                        'student_schedule_id' => $viewer->id,
                        'occupied_at' => Date::now(),
                        'last_seen_at' => Date::now(),
                    ]);
            } catch (QueryException $exception) {
                if ($exception->getCode() === '23000') {
                    throw new CafeSeatUnavailableException;
                }

                throw $exception;
            }

            if ($affected === 0) {
                throw new CafeSeatUnavailableException;
            }

            return $seat->fresh();
        });

        ($this->broadcastCafeChange)('seat.taken', $seat);

        return $seat;
    }
}
