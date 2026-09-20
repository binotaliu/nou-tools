<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use App\Notifications\StudyTimerFinished;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Throwable;

/**
 * Pushes "your timer ran out" to students whose countdown has just
 * expired. Nothing else on the server reacts to `timer_ends_at` passing:
 * a finished countdown simply runs into overtime until the student clicks
 * something, so without this they are only told while the page is visible.
 *
 * Runs as a sub-minute scheduled sweep rather than a job delayed until
 * `timer_ends_at`, which keeps the queue worker out of the path — the
 * same reason `StudyRoomUpdated` broadcasts now instead of queueing — and
 * means pausing, stopping or skipping a round needs no job to be
 * cancelled, because the sweep only ever reads current state.
 */
final readonly class SendStudyTimerEndPushes
{
    /**
     * How far past its end a timer may be and still be worth mentioning.
     * Bounds the catch-up after a scheduler outage: nobody wants to be
     * told about a round that ended half an hour ago.
     */
    private const GRACE_MINUTES = 5;

    public function __invoke(): int
    {
        $now = Date::now();

        $seats = StudyRoomSeat::query()
            ->whereNotNull('student_schedule_id')
            ->whereNotNull('timer_ends_at')
            ->whereNull('paused_at')
            ->whereNull('timer_end_notified_at')
            ->where('timer_ends_at', '<=', $now)
            ->where('timer_ends_at', '>=', $now->copy()->subMinutes(self::GRACE_MINUTES))
            ->whereHas('schedule', fn (Builder $query) => $query
                ->whereHas('pushSubscriptions')
                ->whereHas('studyRoomProfile', fn (Builder $profile) => $profile->where('notify_on_timer_end', true)))
            ->with('schedule.studyRoomProfile')
            ->get();

        $sentCount = 0;

        foreach ($seats as $seat) {
            if (! $this->claim($seat, $now)) {
                continue;
            }

            try {
                $seat->schedule?->notify(new StudyTimerFinished($seat));
            } catch (Throwable $exception) {
                // Per-device delivery outcomes are logged by
                // LogWebPushNotificationSent/Failed; one student's dead
                // subscription must not stop the rest of the sweep.
                report($exception);

                continue;
            }

            $sentCount++;
        }

        return $sentCount;
    }

    /**
     * Mark the seat as told before sending, with the same conditional
     * UPDATE that makes claiming a seat race-free, so two overlapping
     * sweeps cannot both notify the same student.
     */
    private function claim(StudyRoomSeat $seat, \DateTimeInterface $now): bool
    {
        $claimed = StudyRoomSeat::query()
            ->whereKey($seat->getKey())
            ->whereNull('timer_end_notified_at')
            ->update(['timer_end_notified_at' => $now]);

        return $claimed === 1;
    }
}
