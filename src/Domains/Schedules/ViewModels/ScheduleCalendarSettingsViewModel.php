<?php

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ScheduleCalendarSettingsViewModel extends Data
{
    /**
     * @param  array<int, int>  $reminderOffsets
     */
    public function __construct(
        public bool $includeSchoolCalendar,
        public bool $includeExams,
        public bool $classRemindersEnabled,
        public array $reminderOffsets,
    ) {}
}
