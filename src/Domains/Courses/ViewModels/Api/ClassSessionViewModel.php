<?php

namespace NouTools\Domains\Courses\ViewModels\Api;

use App\Models\ClassSchedule;
use Spatie\LaravelData\Data;

/**
 * A single in-person session date within a class section.
 */
final class ClassSessionViewModel extends Data
{
    public function __construct(
        public string $date,
        public string $startTime,
        public string $endTime,
    ) {}

    public static function fromModel(ClassSchedule $schedule): self
    {
        $class = $schedule->courseClass;

        return new self(
            date: $schedule->date->toDateString(),
            startTime: $schedule->start_time ?? $class->start_time,
            endTime: $schedule->end_time ?? $class->end_time,
        );
    }
}
