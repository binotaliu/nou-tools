<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ScheduleAnnouncementsWidgetViewModel extends Data
{
    public function __construct(
        public bool $hasAnySelection,
        #[DataCollectionOf(ScheduleAnnouncementSummaryViewModel::class)]
        public DataCollection $announcements,
        public string $moreAnnouncementsUrl,
    ) {}
}
