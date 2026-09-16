<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Announcements\Actions\ShowAnnouncementIndexPage;
use NouTools\Domains\Announcements\DataTransferObjects\ShowAnnouncementIndexPageData;

final class AnnouncementController extends Controller
{
    public function index(
        ShowAnnouncementIndexPage $showAnnouncementIndexPage,
        ShowAnnouncementIndexPageData $input,
    ): Response {
        return Inertia::render('Announcements/Index', [
            'viewModel' => $showAnnouncementIndexPage($input),
        ]);
    }
}
