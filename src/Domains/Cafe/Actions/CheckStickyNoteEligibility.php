<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeOrder;
use App\Models\CafeStickyNote;
use App\Models\StudentSchedule;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Support\AcornExpiry;
use NouTools\Domains\Cafe\Exceptions\StickyNoteNotAllowedException;

/**
 * The rules for buying a note: not blocked, a coffee bought today and no note
 * yet today (a screened-out or declined one still counts). "Today" is the
 * Taipei day.
 */
final readonly class CheckStickyNoteEligibility
{
    /**
     * @throws StickyNoteNotAllowedException
     */
    public function __invoke(int $scheduleId): void
    {
        if (StudentSchedule::query()->whereKey($scheduleId)->whereNotNull('cafe_blocked_at')->exists()) {
            throw StickyNoteNotAllowedException::blocked();
        }

        $dayStart = AcornExpiry::localDay(Date::now())->utc();

        if (! CafeOrder::query()->where('student_schedule_id', $scheduleId)->where('created_at', '>=', $dayStart)->exists()) {
            throw StickyNoteNotAllowedException::noCoffeeToday();
        }

        if (CafeStickyNote::query()->where('student_schedule_id', $scheduleId)->where('created_at', '>=', $dayStart)->exists()) {
            throw StickyNoteNotAllowedException::alreadyPostedToday();
        }
    }
}
