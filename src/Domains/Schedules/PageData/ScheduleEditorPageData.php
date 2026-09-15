<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\PageData;

use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorSelectedItemViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class ScheduleEditorPageData extends Resource
{
    public function __construct(
        #[DataCollectionOf(ScheduleEditorCourseViewModel::class)]
        public DataCollection $courses,
        public string $currentSemester,
        public string $selectedTerm,
        /** @var array<int, string> */
        public array $availableTerms,
        public ?string $scheduleUuid,
        public ?string $scheduleName,
        #[DataCollectionOf(ScheduleEditorSelectedItemViewModel::class)]
        public ?DataCollection $selectedItems,
        public ?StudentScheduleCookie $previousSchedule,
    ) {}
}
