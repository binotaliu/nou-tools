<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Announcements\Actions\GroupAnnouncementSourceCategories;
use NouTools\Domains\Announcements\Actions\ListAnnouncementSourceCategories;
use NouTools\Domains\Schedules\PageData\AnnouncementPreferencesPageData;

final readonly class BuildAnnouncementPreferencesPage
{
    public function __construct(
        private ListAnnouncementSourceCategories $listAnnouncementSourceCategories,
        private GroupAnnouncementSourceCategories $groupAnnouncementSourceCategories,
    ) {}

    public function __invoke(StudentSchedule $schedule): AnnouncementPreferencesPageData
    {
        $flatCatalog = ($this->listAnnouncementSourceCategories)();
        $groupedCatalog = ($this->groupAnnouncementSourceCategories)();

        $selectedSourceCategories = AnnouncementPreferencesPageData::normalizeSelectedSourceCategories(
            $schedule->announcement_categories,
            $flatCatalog,
            $groupedCatalog,
        );

        return AnnouncementPreferencesPageData::fromCatalog($schedule, $groupedCatalog, $selectedSourceCategories);
    }
}
