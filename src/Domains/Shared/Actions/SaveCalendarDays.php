<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\CalendarDay;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Shared\DataTransferObjects\CalendarDayDTO;

final readonly class SaveCalendarDays
{
    /**
     * Replaces every day override within a calendar year with the given list,
     * so the admin editor can add, change and remove rows in one save.
     *
     * @param  array<int, CalendarDayDTO>  $days
     */
    public function __invoke(int $year, array $days): void
    {
        DB::transaction(function () use ($year, $days): void {
            CalendarDay::query()->between("{$year}-01-01", "{$year}-12-31")->delete();

            foreach ($days as $day) {
                (new CalendarDay)->fillFromDTO($day)->saveOrFail();
            }
        });
    }
}
