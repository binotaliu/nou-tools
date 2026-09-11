<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
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
 * Claiming a second seat *moves* the viewer rather than erroring: any seat
 * they currently hold is released first, inside the same transaction, so a
 * failed claim of the new seat also rolls back the release of the old one.
 * Broadcasting happens only after that transaction returns successfully —
 * `ReleaseSeat` itself never broadcasts, precisely so a seat freed inside
 * this transaction is never announced before we know the whole move
 * actually committed.
 */
final readonly class TakeSeat
{
    public function __construct(
        private ResolveOpenFloorCount $resolveOpenFloorCount,
        private ReleaseSeat $releaseSeat,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer, StudyRoomSeat $seat): StudyRoomSeat
    {
        [$previousSeat, $seat] = DB::transaction(function () use ($viewer, $seat): array {
            if ($seat->floor > ($this->resolveOpenFloorCount)()) {
                throw new FloorClosedException;
            }

            $previousSeat = ($this->releaseSeat)($viewer);

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
                    throw new SeatUnavailableException;
                }

                throw $exception;
            }

            if ($affected === 0) {
                throw new SeatUnavailableException;
            }

            return [$previousSeat, $seat->fresh()];
        });

        if ($previousSeat !== null) {
            ($this->broadcastStudyRoomChange)('seat.left', $previousSeat);
        }

        ($this->broadcastStudyRoomChange)('seat.taken', $seat);

        return $seat;
    }
}
