<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\SchoolCalendarEvent;
use Illuminate\Support\Facades\Date;

final class ListUpcomingSchoolEvents
{
    /**
     * Raw events for a semester, keyed as plain Y-m-d strings. Status,
     * days-until, and which event to show as the countdown are computed
     * client-side (see resources/js/Composables/useSchoolCalendar.js),
     * anchored to the viewer's Taipei calendar date rather than the server
     * clock.
     *
     * For the current semester (the default), only still-relevant events
     * (not yet ended) are returned. For any other semester explicitly
     * requested via $term, all of that semester's events are returned,
     * since a past semester's calendar has no "upcoming" events to hide.
     *
     * Only events flagged important are returned unless $onlyImportant is
     * false; the full calendar page lists everything.
     *
     * @return array<int, array{start: string, end: string, name: string, countdown: bool}>
     */
    public function __invoke(?string $referenceDate = null, ?string $term = null, bool $onlyImportant = true): array
    {
        $currentSemester = (string) config('app.current_semester');
        $semester = $term ?: $currentSemester;
        $showAllEvents = $term !== null && $term !== $currentSemester;

        $today = ($referenceDate
            ? Date::parse($referenceDate, 'Asia/Taipei')
            : Date::now('Asia/Taipei'))->format('Y-m-d');

        return SchoolCalendarEvent::query()
            ->forTerm($semester)
            ->when($onlyImportant, fn ($query) => $query->important())
            ->when(! $showAllEvents, fn ($query) => $query->where('end_date', '>=', $today))
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->get()
            ->map(fn (SchoolCalendarEvent $event): array => [
                'start' => $event->start_date->format('Y-m-d'),
                'end' => $event->end_date->format('Y-m-d'),
                'name' => $event->name,
                'countdown' => $event->is_countdown,
            ])
            ->all();
    }
}
