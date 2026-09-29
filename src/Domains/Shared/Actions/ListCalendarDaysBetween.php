<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\CalendarDay;
use NouTools\Domains\Shared\ViewModels\CalendarDayViewModel;

final readonly class ListCalendarDaysBetween
{
    /**
     * Day overrides within the inclusive [$from, $to] date range.
     *
     * @return array<int, CalendarDayViewModel>
     */
    public function __invoke(string $from, string $to): array
    {
        return CalendarDay::query()
            ->between($from, $to)
            ->orderBy('date')
            ->get()
            ->map(fn (CalendarDay $day): CalendarDayViewModel => CalendarDayViewModel::fromModel($day))
            ->all();
    }
}
