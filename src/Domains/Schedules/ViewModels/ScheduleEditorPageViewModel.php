<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentSchedule;

final readonly class ScheduleEditorPageViewModel
{
    public function __construct(
        public array $courses,
        public string $currentSemester,
        public ?StudentSchedule $schedule,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}
}
