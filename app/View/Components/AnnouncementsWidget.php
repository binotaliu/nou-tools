<?php

namespace App\View\Components;

use App\Models\Announcement;
use App\Models\StudentSchedule;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use NouTools\Domains\Announcements\Actions\GroupAnnouncementSourceCategories;
use NouTools\Domains\Announcements\Actions\ListAnnouncementSourceCategories;
use NouTools\Domains\Announcements\Actions\ListLatestAnnouncementsForSourceCategories;
use NouTools\Domains\Schedules\ViewModels\AnnouncementPreferencesPageViewModel;

class AnnouncementsWidget extends Component
{
    public StudentSchedule $schedule;

    /** @var Collection<int, Announcement> */
    public Collection $announcements;

    public bool $hasAnySelection;

    /** @var array<string, array<int, string>> */
    public array $selectedSourceCategories;

    public function __construct(
        StudentSchedule $schedule,
        ListAnnouncementSourceCategories $listAnnouncementSourceCategories,
        GroupAnnouncementSourceCategories $groupAnnouncementSourceCategories,
        ListLatestAnnouncementsForSourceCategories $listLatestAnnouncementsForSourceCategories,
    ) {
        $this->schedule = $schedule;

        $selectedSourceCategories = AnnouncementPreferencesPageViewModel::normalizeSelectedSourceCategories(
            $schedule->announcement_categories,
            $listAnnouncementSourceCategories(),
            $groupAnnouncementSourceCategories(),
        );

        $this->selectedSourceCategories = $selectedSourceCategories;
        $this->hasAnySelection = $selectedSourceCategories !== [];
        $this->announcements = $listLatestAnnouncementsForSourceCategories($selectedSourceCategories, 10);
    }

    public function render(): View
    {
        return view('components.announcements-widget');
    }
}
