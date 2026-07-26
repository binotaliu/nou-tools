<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\Announcements\Actions\ShowAnnouncementIndexPage;
use NouTools\Domains\Announcements\DataTransferObjects\ShowAnnouncementIndexPageData;

final class AnnouncementIndexMarkdownController extends Controller
{
    public function __invoke(
        ShowAnnouncementIndexPage $showAnnouncementIndexPage,
        ShowAnnouncementIndexPageData $input,
    ): Response {
        return response()
            ->view('announcements.markdown.index', ['viewModel' => $showAnnouncementIndexPage($input)])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
