<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\DataTransferObjects;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class SchoolCalendarEventDTO extends Data
{
    public function __construct(
        public CarbonInterface $startDate,
        public CarbonInterface $endDate,
        public string $name,
        public bool $isCountdown = false,
        public bool $isImportant = true,
    ) {}
}
