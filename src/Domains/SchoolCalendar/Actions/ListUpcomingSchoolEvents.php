<?php

namespace NouTools\Domains\SchoolCalendar\Actions;

use Carbon\Carbon;

final class ListUpcomingSchoolEvents
{
    public function getUpcomingAndOngoingEvents(?string $referenceDate = null): array
    {
        $semester = config('app.current_semester');
        $schedules = config('school-schedules.'.(string) $semester, []);

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

            if ($end->gte($now->copy()->startOfDay())) {
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

        usort($events, fn (array $left, array $right) => $left['start']->timestamp <=> $right['start']->timestamp);

        return $events;
    }

    public function getCountdownEvent(?string $referenceDate = null): ?array
    {
        foreach ($this->getUpcomingAndOngoingEvents($referenceDate) as $event) {
            if ($event['countdown'] && in_array($event['status'], ['upcoming', 'ongoing'], true)) {
                return $event;
            }
        }

        return null;
    }

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
