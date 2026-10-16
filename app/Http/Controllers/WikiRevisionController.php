<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WikiRevision;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\FindWikiPageBySlug;
use NouTools\Domains\Wiki\Actions\ShowWikiRevisionPage;

final class WikiRevisionController extends Controller
{
    public function __invoke(string $slug, int $revision, FindWikiPageBySlug $findWikiPageBySlug, ShowWikiRevisionPage $showWikiRevisionPage): Response|RedirectResponse
    {
        $page = $findWikiPageBySlug($slug);

        abort_if($page === null, 404);

        $wikiRevision = WikiRevision::query()->where('wiki_page_id', $page->id)->findOrFail($revision);

        if ($page->slug !== $slug) {
            return redirect()->route('wiki.revision', ['slug' => $page->slug, 'revision' => $wikiRevision->id], 301);
        }

        return Inertia::render('Wiki/Revision', [
            'viewModel' => $showWikiRevisionPage($page, $wikiRevision),
        ]);
    }
}
