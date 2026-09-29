<?php

declare(strict_types=1);

namespace NouTools\Domains\SchoolCalendar\PageData;

use NouTools\Domains\Shared\ViewModels\CalendarDayViewModel;
use Spatie\LaravelData\Resource;

final class SchoolCalendarPageData extends Resource
{
    /**
     * @param  array<int, array{code: string, label: string}>  $terms
     * @param  array<int, array{start: string, end: string, name: string, countdown: bool, important: bool}>  $events
     * @param  array<int, CalendarDayViewModel>  $days
     */
    public function __construct(
        public string $term,
        public string $termLabel,
        public bool $isCurrentTerm,
        public array $terms,
        public array $events,
        public array $days,
    ) {}
}
