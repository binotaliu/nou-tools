<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\SchoolCalendarEvent;

final readonly class ListSchoolEventsBetween
{
    /**
     * Events from every semester (important or not) that overlap the
     * inclusive [$from, $to] date range. All semesters are scanned, not just
     * the current one, since a short window can straddle a semester boundary.
     *
     * @return array<int, array{start: string, end: string, name: string}>
     */
    public function __invoke(string $from, string $to): array
    {
        return SchoolCalendarEvent::query()
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->get()
            ->map(fn (SchoolCalendarEvent $event): array => [
                'start' => $event->start_date->format('Y-m-d'),
                'end' => $event->end_date->format('Y-m-d'),
                'name' => $event->name,
            ])
            ->unique(fn (array $event): string => $event['start'].'|'.$event['name'])
            ->values()
            ->all();
    }
}
