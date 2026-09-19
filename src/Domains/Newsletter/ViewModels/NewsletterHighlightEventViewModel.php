<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use Spatie\LaravelData\Data;

/**
 * A school-calendar event as snapshotted into an issue when it was drafted.
 */
final class NewsletterHighlightEventViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $startDate,
        public string $endDate,
        public ?string $description = null,
    ) {}

    /**
     * @param  array{name: string, start: string, end: string, description?: string|null}  $event
     */
    public static function fromSnapshot(array $event): self
    {
        return new self(
            name: $event['name'],
            startDate: $event['start'],
            endDate: $event['end'],
            description: filled($event['description'] ?? null) ? $event['description'] : null,
        );
    }
}
