<?php

namespace App\Services;

use Carbon\Carbon;

class SchoolScheduleService
{
    /**
     * Get upcoming and ongoing schedule items for current semester.
     */
    public function getUpcomingAndOngoingEvents(?string $referenceDate = null): array
    {
        $semester = config('app.current_semester');
        $schedules = config("school-schedules.{$semester}", []);

        if (empty($schedules)) {
            return [];
        }

        $now = $referenceDate
            ? Carbon::parse($referenceDate, 'Asia/Taipei')
            : Carbon::now('Asia/Taipei');

        $events = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule['start'], 'Asia/Taipei');
            $end = Carbon::parse($schedule['end'], 'Asia/Taipei');

            // Include if event is ongoing (today is between start and end)
            // or upcoming (start date is in the future)
            if ($end->gte($now->startOfDay())) {
                $events[] = [
                    'start' => $start,
                    'end' => $end,
                    'name' => $schedule['name'],
                    'countdown' => $schedule['countdown'],
                    'status' => $this->getEventStatus($start, $end, $now),
                    'daysUntil' => $this->calculateDaysUntil($start, $now),
                ];
            }
        }

        // Sort by start date
        usort($events, fn ($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        return $events;
    }

    /**
     * Get the countdown event (if any).
     * Returns the nearest upcoming or ongoing event with countdown === true.
     */
    public function getCountdownEvent(?string $referenceDate = null): ?array
    {
        $events = $this->getUpcomingAndOngoingEvents($referenceDate);

        foreach ($events as $event) {
            if ($event['countdown'] && ($event['status'] === 'upcoming' || $event['status'] === 'ongoing')) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Determine event status: upcoming or ongoing.
     */
    private function getEventStatus(Carbon $start, Carbon $end, Carbon $now): string
    {
        $nowDate = $now->copy()->startOfDay();
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        if ($nowDate->gte($startDate) && $nowDate->lte($endDate)) {
            return 'ongoing';
        }

        return 'upcoming';
    }

    /**
     * Calculate days until event starts (0 if ongoing or past).
     */
    private function calculateDaysUntil(Carbon $start, Carbon $now): int
    {
        $nowDate = $now->copy()->startOfDay();
        $startDate = $start->copy()->startOfDay();

        if ($nowDate->gte($startDate)) {
            return 0;
        }

        return (int) $nowDate->diffInDays($startDate);
    }
}
