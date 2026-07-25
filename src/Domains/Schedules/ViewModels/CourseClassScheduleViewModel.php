<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\ClassSchedule;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class CourseClassScheduleViewModel extends Data
{
    public function __construct(
        public CarbonInterface $date,
        public ?string $startTime,
        public ?string $endTime,
    ) {}

    public static function fromModel(ClassSchedule $schedule): self
    {
        return new self(
            date: $schedule->date,
            startTime: $schedule->start_time,
            endTime: $schedule->end_time,
        );
    }
}
