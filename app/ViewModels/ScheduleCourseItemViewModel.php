<?php

namespace App\ViewModels;

use DateTimeInterface;

final readonly class ScheduleCourseItemViewModel
{
    public function __construct(
        public string $courseName,
        public string $code,
        public string $time,
        public bool $hasOverride,
        public DateTimeInterface $date,
    ) {}
}
