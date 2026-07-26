<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\PageData;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorCourseViewModel;
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
        public ?StudentScheduleCookie $previousSchedule,
    ) {}
}
