<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use DateTimeInterface;
use Spatie\LaravelData\Data;

final class ScheduleCourseItemViewModel extends Data
{
    public function __construct(
        public string $courseName,
        public string $code,
        public string $time,
        public bool $hasOverride,
        public DateTimeInterface $date,
    ) {}
}
