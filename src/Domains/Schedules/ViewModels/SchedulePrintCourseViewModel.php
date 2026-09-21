<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

final class SchedulePrintCourseViewModel extends Data
{
    public function __construct(
        public string $name,
        public ?int $credits,
    ) {}
}
