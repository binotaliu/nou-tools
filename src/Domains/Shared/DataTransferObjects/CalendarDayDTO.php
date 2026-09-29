<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\DataTransferObjects;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class CalendarDayDTO extends Data
{
    public function __construct(
        public CarbonInterface $date,
        public bool $isRed = true,
        public ?string $label = null,
    ) {}
}
