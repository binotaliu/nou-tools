<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeOrder;
use Illuminate\Support\Facades\Date;

/**
 * Throws away what a student ordered for the table (內用) when they leave the
 * café seat. 外帶 keeps its own lifetime and leaves with them.
 */
final readonly class TossDineInOrders
{
    public function __invoke(int $studentScheduleId): void
    {
        $now = Date::now();

        CafeOrder::query()
            ->where('student_schedule_id', $studentScheduleId)
            ->where('to_go', false)
            ->where('expires_at', '>', $now)
            ->update(['expires_at' => $now]);
    }
}
