<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiPage;
use NouTools\Domains\Wiki\PageData\WikiAllPagesPageData;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;

/**
 * 所有條目 (and the Markdown twin of the homepage): every listed page by
 * title. The homepage itself no longer lists them all.
 */
final readonly class ShowWikiAllPagesPage
{
    public function __invoke(): WikiAllPagesPageData
    {
        $pages = WikiPage::query()
            ->listed()
            ->with('currentRevision:id,title,description')
            ->get()
            ->sortBy(fn (WikiPage $page): string => (string) $page->currentRevision?->title)
            ->map(fn (WikiPage $page): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($page))
            ->values()
            ->all();

        return new WikiAllPagesPageData(
            wikiTitle: (string) config('wiki.title'),
            pages: $pages,
            newPageUrl: route('wiki.new'),
        );
    }
}
