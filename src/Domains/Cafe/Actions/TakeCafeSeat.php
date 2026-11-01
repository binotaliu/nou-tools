<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeSeat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Cafe\Exceptions\CafeAlreadySeatedException;
use NouTools\Domains\Cafe\Exceptions\CafeSeatUnavailableException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Claims an empty café seat with one conditional UPDATE (the same race-safe
 * approach as the study room's `TakeSeat`). Switching seats is not allowed:
 * leave first.
 */
final readonly class TakeCafeSeat
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    public function __invoke(StudentScheduleCookie $viewer, CafeSeat $seat): CafeSeat
    {
        $seat = DB::transaction(function () use ($viewer, $seat): CafeSeat {
            if (CafeSeat::query()->where('student_schedule_id', $viewer->id)->exists()) {
                throw new CafeAlreadySeatedException;
            }

            try {
                $affected = CafeSeat::query()
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
