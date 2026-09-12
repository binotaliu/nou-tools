<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Releases every occupied seat that has gone `study-room.seat_without_timer
 * .grace_seconds` without an active timer — whether the student never
 * started one after claiming the seat, or started and later stopped one
 * and never started another. An occupied-but-idle seat otherwise blocks
 * other students without anyone actually studying.
 *
 * Keyed off `no_timer_since`, not `occupied_at`: `occupied_at` is fixed at
 * claim time, so using it here would also catch a student who studied for
 * hours and stopped their timer moments ago. `no_timer_since` is instead
 * reset to now every time a running timer stops (and cleared whenever one
 * starts), so it always reflects how long the *current* no-timer stretch
 * has actually lasted.
 */
final readonly class ReleaseSeatsWithoutTimer
{
    public function __construct(
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(): int
    {
        $graceSeconds = (int) config('study-room.seat_without_timer.grace_seconds');
        $cutoff = Date::now()->subSeconds($graceSeconds);

        $seats = StudyRoomSeat::query()
            ->whereNotNull('student_schedule_id')
            ->whereNull('timer_started_at')
            ->whereNotNull('no_timer_since')
            ->where('no_timer_since', '<', $cutoff)
            ->get();

        foreach ($seats as $seat) {
            DB::transaction(function () use ($seat): void {
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
            });

            ($this->broadcastStudyRoomChange)('seat.no-timer-released', $seat);
        }

        return $seats->count();
    }
}
