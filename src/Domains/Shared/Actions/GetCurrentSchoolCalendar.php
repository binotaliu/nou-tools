<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\SchoolCalendarEvent;
use NouTools\Domains\Shared\ViewModels\SchoolCalendarEventViewModel;
use Spatie\LaravelData\DataCollection;

/**
 * Returns the important school calendar events for the current semester.
 */
final readonly class GetCurrentSchoolCalendar
{
    public function __invoke(): DataCollection
    {
        $events = SchoolCalendarEvent::query()
            ->forTerm((string) config('app.current_semester'))
            ->important()
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->get()
            ->map(fn (SchoolCalendarEvent $event) => SchoolCalendarEventViewModel::fromModel($event));

        return SchoolCalendarEventViewModel::collect($events, DataCollection::class);
    }
}
