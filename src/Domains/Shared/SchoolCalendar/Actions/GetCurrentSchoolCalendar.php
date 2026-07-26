<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\SchoolCalendar\Actions;

use NouTools\Domains\Shared\SchoolCalendar\ViewModels\SchoolCalendarEventViewModel;
use Spatie\LaravelData\DataCollection;

/**
 * Returns all school calendar events for the current semester.
 */
final readonly class GetCurrentSchoolCalendar
{
    public function __invoke(): DataCollection
    {
        $term = config('app.current_semester');

        /** @var array<int, array{name: string, start: string, end: string, countdown: bool}> $events */
        $events = config('school-schedules.'.$term, []);

        $events = collect($events)->map(
            fn (array $event) => SchoolCalendarEventViewModel::fromConfig($event),
        );

        return SchoolCalendarEventViewModel::collect($events, DataCollection::class);
    }
}
