<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\ViewModels;

use App\Models\SchoolCalendarEvent;
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

    public static function fromModel(SchoolCalendarEvent $event): self
    {
        return new self(
            name: $event->name,
            startDate: $event->start_date->format('Y-m-d'),
            endDate: $event->end_date->format('Y-m-d'),
            isCountdown: $event->is_countdown,
        );
    }
}
