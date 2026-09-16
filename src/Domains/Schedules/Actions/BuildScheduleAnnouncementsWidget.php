<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Announcements\Actions\GroupAnnouncementSourceCategories;
use NouTools\Domains\Announcements\Actions\ListAnnouncementSourceCategories;
use NouTools\Domains\Announcements\Actions\ListLatestAnnouncementsForSourceCategories;
use NouTools\Domains\Schedules\PageData\AnnouncementPreferencesPageData;
use NouTools\Domains\Schedules\ViewModels\ScheduleAnnouncementSummaryViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleAnnouncementsWidgetViewModel;
use Spatie\LaravelData\DataCollection;

/**
 * Builds the props for resources/js/Components/AnnouncementsWidget.vue,
 * which renders the widget client-side.
 */
final readonly class BuildScheduleAnnouncementsWidget
{
    public function __construct(
        private ListAnnouncementSourceCategories $listAnnouncementSourceCategories,
        private GroupAnnouncementSourceCategories $groupAnnouncementSourceCategories,
        private ListLatestAnnouncementsForSourceCategories $listLatestAnnouncementsForSourceCategories,
    ) {}

    public function __invoke(StudentSchedule $schedule): ScheduleAnnouncementsWidgetViewModel
    {
        $selectedSourceCategories = AnnouncementPreferencesPageData::normalizeSelectedSourceCategories(
            $schedule->announcement_categories,
            ($this->listAnnouncementSourceCategories)(),
            ($this->groupAnnouncementSourceCategories)(),
        );

        $announcements = ScheduleAnnouncementSummaryViewModel::collect(
            ($this->listLatestAnnouncementsForSourceCategories)($selectedSourceCategories, 10)
                ->map(fn ($announcement) => ScheduleAnnouncementSummaryViewModel::fromModel($announcement)),
            DataCollection::class,
        );

        return new ScheduleAnnouncementsWidgetViewModel(
            hasAnySelection: $selectedSourceCategories !== [],
            announcements: $announcements,
            moreAnnouncementsUrl: route('announcements.index', ['source_categories' => $selectedSourceCategories]),
        );
    }
}
