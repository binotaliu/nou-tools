<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

final class SchedulePrintMonthViewModel extends Data
{
    public function __construct(
        public string $title,
        /** @var array<int, array<int, array{day: int, hasClass: bool}|null>> Monday-first weeks of seven cells; null pads days outside the month. */
        public array $weeks,
    ) {}
}
