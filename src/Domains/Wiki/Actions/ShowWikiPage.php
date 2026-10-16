<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiLink;
use App\Models\WikiPage;
use NouTools\Domains\Wiki\PageData\WikiShowPageData;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageViewModel;

final readonly class ShowWikiPage
{
    public function __construct(private RenderWikiMarkdown $renderWikiMarkdown) {}

    public function __invoke(WikiPage $page): WikiShowPageData
    {
        $page->loadMissing(['currentRevision', 'slugRedirects']);

        $incomingSlugs = [$page->slug, ...$page->slugRedirects->pluck('old_slug')->all()];

        $backlinks = WikiPage::query()
            ->live()
            ->whereIn('id', WikiLink::query()->whereIn('to_slug', $incomingSlugs)->select('from_page_id'))
            ->with('currentRevision:id,title,description')
            ->get()
            ->sortBy(fn (WikiPage $linking): string => (string) $linking->currentRevision?->title)
            ->map(fn (WikiPage $linking): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($linking))
            ->values()
            ->all();

        return new WikiShowPageData(
            wikiTitle: (string) config('wiki.title'),
            page: WikiPageViewModel::fromModel($page, $page->currentRevision, $this->renderWikiMarkdown),
            backlinks: $backlinks,
        );
    }
}
