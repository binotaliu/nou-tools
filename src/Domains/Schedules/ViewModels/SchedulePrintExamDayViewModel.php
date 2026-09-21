<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

final class SchedulePrintExamDayViewModel extends Data
{
    public function __construct(
        public string $dateKey,
        public string $dateLabel,
        /** @var array<int, array{kind: string, time: ?string, courseName: string}> Ordered by session start. */
        public array $entries,
    ) {}
}
