<?php

namespace NouTools\Domains\SchoolCalendar\Actions;

use Carbon\Carbon;

final class ListUpcomingSchoolEvents
{
    /**
     * Raw upcoming/ongoing events for the current semester, keyed as plain
     * Y-m-d strings. Status, days-until, and which event to show as the
     * countdown are computed client-side (see window.nouSchoolCalendar in
     * resources/js/app.js), anchored to the viewer's Taipei calendar date
     * rather than the server clock.
     *
     * @return array<int, array{start: string, end: string, name: string, countdown: bool}>
     */
    public function getUpcomingEvents(?string $referenceDate = null): array
    {
        $semester = config('app.current_semester');
        $schedules = config('school-schedules.'.(string) $semester, []);

        if (empty($schedules)) {
            return [];
        }

        $now = $referenceDate
            ? Carbon::parse($referenceDate, 'Asia/Taipei')
            : Carbon::now('Asia/Taipei');

        $today = $now->copy()->startOfDay();

        $events = [];

        foreach ($schedules as $schedule) {
            $end = Carbon::parse($schedule['end'], 'Asia/Taipei');

            if ($end->gte($today)) {
                $events[] = [
                    'start' => Carbon::parse($schedule['start'], 'Asia/Taipei')->format('Y-m-d'),
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
