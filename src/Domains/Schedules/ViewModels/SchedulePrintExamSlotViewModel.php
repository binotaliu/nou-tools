<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

final class SchedulePrintExamSlotViewModel extends Data
{
    public function __construct(
        public string $dateKey,
        public string $dateLabel,
        public string $kind,
        public ?string $time,
        /** @var array<int, string> */
        public array $courseNames,
    ) {}
}
