<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use NouTools\Domains\Announcements\Actions\ShowAnnouncementIndexPage;
use NouTools\Domains\Announcements\DataTransferObjects\ShowAnnouncementIndexPageData;

class AnnouncementController extends Controller
{
    public function index(
        ShowAnnouncementIndexPage $showAnnouncementIndexPage,
        ShowAnnouncementIndexPageData $input,
    ): View {
        $page = $showAnnouncementIndexPage($input);

        return view('announcements.index', [
            'announcements' => $page->announcements,
            'availableSources' => $page->availableSources,
            'availableCategories' => $page->availableCategories,
            'sourceCategorySelections' => $page->sourceCategorySelections,
            'selectedSources' => $page->selectedSources,
            'totalAnnouncements' => $page->totalAnnouncements,
        ]);
    }
}
