<?php

declare(strict_types=1);

namespace NouTools\Domains\SchoolCalendar\Actions;

use App\Models\SchoolCalendarEvent;
use Illuminate\Support\Str;
use NouTools\Domains\SchoolCalendar\DataTransferObjects\ShowSchoolCalendarData;
use NouTools\Domains\SchoolCalendar\PageData\SchoolCalendarPageData;
use NouTools\Domains\Shared\Actions\ListCalendarDaysBetween;

final readonly class ShowSchoolCalendarPage
{
    public function __construct(private ListCalendarDaysBetween $listCalendarDaysBetween) {}

    /**
     * Every event of a semester, important or not, oldest first. Whether an
     * event is past, ongoing or upcoming is decided in the browser, like the
     * home page card (see useSchoolCalendar.js).
     */
    public function __invoke(ShowSchoolCalendarData $input): SchoolCalendarPageData
    {
        $currentTerm = (string) config('app.current_semester');
        $term = $input->term ?? $currentTerm;

        $terms = SchoolCalendarEvent::query()
            ->distinct()
            ->pluck('term')
            ->push($currentTerm, $term)
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn (string $code): array => ['code' => $code, 'label' => Str::toSemesterDisplay($code)])
            ->all();

        $events = SchoolCalendarEvent::query()
            ->forTerm($term)
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->get()
            ->map(fn (SchoolCalendarEvent $event): array => [
                'start' => $event->start_date->format('Y-m-d'),
                'end' => $event->end_date->format('Y-m-d'),
                'name' => $event->name,
                'countdown' => $event->is_countdown,
                'important' => $event->is_important,
            ])
            ->all();

        $days = $events === []
            ? []
            : ($this->listCalendarDaysBetween)(
                min(array_column($events, 'start')),
                max(array_column($events, 'end')),
            );

        return new SchoolCalendarPageData(
            term: $term,
            termLabel: Str::toSemesterDisplay($term),
            isCurrentTerm: $term === $currentTerm,
            terms: $terms,
            events: $events,
            days: $days,
        );
    }
}
