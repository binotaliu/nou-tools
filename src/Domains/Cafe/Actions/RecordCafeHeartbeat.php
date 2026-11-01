<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeSeat;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

final readonly class RecordCafeHeartbeat
{
    public function __invoke(StudentScheduleCookie $viewer): bool
    {
        $seat = CafeSeat::query()->where('student_schedule_id', $viewer->id)->first();

        if ($seat === null) {
            return false;
        }

        $seat->last_seen_at = Date::now();
        $seat->saveOrFail();

        return true;
    }
}
