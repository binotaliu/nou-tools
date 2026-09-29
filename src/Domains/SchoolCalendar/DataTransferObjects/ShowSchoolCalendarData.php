<?php

declare(strict_types=1);

namespace NouTools\Domains\SchoolCalendar\DataTransferObjects;

use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

final class ShowSchoolCalendarData extends Data
{
    public function __construct(
        #[Regex('/^\d{4}[ABC]$/')]
        public ?string $term = null,
    ) {}
}
