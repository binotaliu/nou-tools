<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\FindWikiPageBySlug;
use NouTools\Domains\Wiki\Actions\ShowWikiHistoryPage;

final class WikiHistoryController extends Controller
{
    public function __invoke(string $slug, FindWikiPageBySlug $findWikiPageBySlug, ShowWikiHistoryPage $showWikiHistoryPage): Response|RedirectResponse
    {
        $page = $findWikiPageBySlug($slug);

        abort_if($page === null, 404);

        if ($page->slug !== $slug) {
            return redirect()->route('wiki.history', $page->slug, 301);
        }

        return Inertia::render('Wiki/History', [
            'viewModel' => $showWikiHistoryPage($page),
        ]);
    }
}
