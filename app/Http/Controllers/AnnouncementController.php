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
        return view('announcements.index', [
            'viewModel' => $showAnnouncementIndexPage($input),
        ]);
    }
}
