<?php

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ScheduleDisplayOptionsViewModel extends Data
{
    public function __construct(
        public bool $showGreeting,
        public bool $showScheduleItems,
        public bool $showCommonLinks,
        public bool $showClassDates,
        public bool $showSchoolCalendar,
        public bool $showExamInfo,
        public bool $showAnnouncements,
        public bool $showShareSection,
        public bool $showPrintButton,
    ) {}
}
