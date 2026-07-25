<?php

namespace NouTools\Domains\Schedules\PageData;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class ScheduleEditorPageData extends Resource
{
    public function __construct(
        #[DataCollectionOf(ScheduleEditorCourseViewModel::class)]
        public DataCollection $courses,
        public string $currentSemester,
        public ?StudentSchedule $schedule,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}
}
