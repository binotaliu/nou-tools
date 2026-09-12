<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Bumps `last_seen_at` on the viewer's held seat, keeping it alive for
 * `ReleaseIdleSeats`. Deliberately does nothing to a running timer, even
 * one whose `timer_ends_at` has already passed: countdowns keep ticking
 * into overtime until the student acts (開始休息 / 下一輪 / 結束), and only
 * that explicit action — or `ReleaseSeat`/`ReleaseIdleSeats` releasing the
 * seat out from under them — finalizes the session via
 * `RecordStudySession`.
 */
final readonly class RecordHeartbeat
{
    public function __invoke(StudentScheduleCookie $viewer): bool
    {
        $seat = StudyRoomSeat::query()
            ->where('student_schedule_id', $viewer->id)
            ->first();

        if ($seat === null) {
            return false;
        }

        $seat->last_seen_at = Date::now();
        $seat->saveOrFail();

        return true;
    }
}
