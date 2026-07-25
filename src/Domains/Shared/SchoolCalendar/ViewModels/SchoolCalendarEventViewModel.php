<?php

namespace NouTools\Domains\SchoolCalendar\ViewModels;

use Spatie\LaravelData\Data;

/**
 * A single school calendar event (學校行事曆).
 */
final class SchoolCalendarEventViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $startDate,
        public string $endDate,
        public bool $isCountdown,
    ) {}

    /**
     * @param  array{name: string, start: string, end: string, countdown: bool}  $event
     */
    public static function fromConfig(array $event): self
    {
        return new self(
            name: $event['name'],
            startDate: $event['start'],
            endDate: $event['end'],
            isCountdown: $event['countdown'],
        );
    }
}
