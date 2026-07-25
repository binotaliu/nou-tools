<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Announcements\Actions\GroupAnnouncementSourceCategories;
use NouTools\Domains\Announcements\Actions\ListAnnouncementSourceCategories;
use NouTools\Domains\Schedules\ViewModels\AnnouncementPreferencesPageViewModel;

final readonly class BuildAnnouncementPreferencesPage
{
    public function __construct(
        private ListAnnouncementSourceCategories $listAnnouncementSourceCategories,
        private GroupAnnouncementSourceCategories $groupAnnouncementSourceCategories,
    ) {}

    public function __invoke(StudentSchedule $schedule): AnnouncementPreferencesPageViewModel
    {
        $flatCatalog = ($this->listAnnouncementSourceCategories)();
        $groupedCatalog = ($this->groupAnnouncementSourceCategories)();

        $selectedSourceCategories = AnnouncementPreferencesPageViewModel::normalizeSelectedSourceCategories(
            $schedule->announcement_categories,
            $flatCatalog,
            $groupedCatalog,
        );

        return AnnouncementPreferencesPageViewModel::fromCatalog($schedule, $groupedCatalog, $selectedSourceCategories);
    }
}
