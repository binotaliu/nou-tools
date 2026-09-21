<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

/**
 * One course's exams. Midterm and final share a time slot (only the dates
 * differ), so the time is stored once and the dates are split by weekday.
 */
final class SchedulePrintExamRowViewModel extends Data
{
    public function __construct(
        public string $courseName,
        public ?string $time,
        /** @var array<int, array{kind: string, label: string}> */
        public array $saturday,
        /** @var array<int, array{kind: string, label: string}> */
        public array $sunday,
        /** @var array<int, array{kind: string, label: string}> Weekday dates, so bad data never silently disappears. */
        public array $other,
    ) {}
}
