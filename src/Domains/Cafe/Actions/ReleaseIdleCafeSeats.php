<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeSeat;
use Illuminate\Support\Facades\Date;

final readonly class ReleaseIdleCafeSeats
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    public function __invoke(): int
    {
        $cutoff = Date::now()->subSeconds((int) config('cafe.heartbeat.idle_release_seconds'));

        $idleSeats = CafeSeat::query()
            ->whereNotNull('student_schedule_id')
            ->where('last_seen_at', '<', $cutoff)
            ->get();

        foreach ($idleSeats as $seat) {
            $seat->student_schedule_id = null;
            $seat->occupied_at = null;
            $seat->last_seen_at = null;
            $seat->saveOrFail();

            ($this->broadcastCafeChange)('seat.idle-released', $seat);
        }

        return $idleSeats->count();
    }
}
