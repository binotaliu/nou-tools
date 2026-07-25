<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentSchedule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ScheduleEditorPageViewModel extends Data
{
    public function __construct(
        #[DataCollectionOf(ScheduleEditorCourseViewModel::class)]
        public DataCollection $courses,
        public string $currentSemester,
        public ?StudentSchedule $schedule,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}
}
