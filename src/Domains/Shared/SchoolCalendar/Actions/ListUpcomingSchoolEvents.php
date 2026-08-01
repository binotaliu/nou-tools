<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\SchoolCalendar\Actions;

use Illuminate\Support\Facades\Date;

final class ListUpcomingSchoolEvents
{
    /**
     * Raw events for a semester, keyed as plain Y-m-d strings. Status,
     * days-until, and which event to show as the countdown are computed
     * client-side (see window.nouSchoolCalendar in resources/js/app.js),
     * anchored to the viewer's Taipei calendar date rather than the server
     * clock.
     *
     * For the current semester (the default), only still-relevant events
     * (not yet ended) are returned. For any other semester explicitly
     * requested via $term, all of that semester's events are returned,
     * since a past semester's calendar has no "upcoming" events to hide.
     *
     * @return array<int, array{start: string, end: string, name: string, countdown: bool}>
     */
    public function __invoke(?string $referenceDate = null, ?string $term = null): array
    {
        $currentSemester = (string) config('app.current_semester');
        $semester = $term ?: $currentSemester;
        $showAllEvents = $term !== null && $term !== $currentSemester;

        $schedules = config('school-schedules.'.$semester, []);

        if (empty($schedules)) {
            return [];
        }

        $now = $referenceDate
            ? Date::parse($referenceDate, 'Asia/Taipei')
            : Date::now('Asia/Taipei');

        $today = $now->copy()->startOfDay();

        $events = [];

        foreach ($schedules as $schedule) {
            $end = Date::parse($schedule['end'], 'Asia/Taipei');

            if ($showAllEvents || $end->gte($today)) {
                $events[] = [
                    'start' => Date::parse($schedule['start'], 'Asia/Taipei')->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                    'name' => $schedule['name'],
                    'countdown' => $schedule['countdown'],
                ];
            }
        }

        usort($events, fn (array $left, array $right) => $left['start'] <=> $right['start']);

        return $events;
    }
}
