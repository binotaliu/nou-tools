<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

final readonly class ListSchoolEventsBetween
{
    /**
     * Events from every configured semester that overlap the inclusive
     * [$from, $to] date range. All semesters are scanned, not just the
     * current one, since a short window can straddle a semester boundary.
     *
     * @return array<int, array{start: string, end: string, name: string}>
     */
    public function __invoke(string $from, string $to): array
    {
        $events = collect(config('school-schedules', []))
            ->flatten(1)
            ->filter(fn (mixed $event): bool => is_array($event)
                && $event['start'] <= $to
                && $event['end'] >= $from)
            ->map(fn (array $event): array => [
                'start' => $event['start'],
                'end' => $event['end'],
                'name' => $event['name'],
            ])
            ->unique(fn (array $event): string => $event['start'].'|'.$event['name'])
            ->sortBy([['start', 'asc'], ['end', 'asc']])
            ->values()
            ->all();

        return $events;
    }
}
