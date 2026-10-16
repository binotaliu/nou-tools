<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiCategory;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use NouTools\Domains\Wiki\PageData\WikiIndexPageData;
use NouTools\Domains\Wiki\ViewModels\WikiCategoryLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;

final readonly class ShowWikiIndexPage
{
    private const int RECENT_CHANGES = 15;

    private const int SEARCH_RESULTS = 30;

    public function __invoke(string $query = ''): WikiIndexPageData
    {
        $query = mb_substr(trim($query), 0, 100);

        $recentChanges = WikiRevision::query()
            ->join('wiki_pages', 'wiki_pages.id', '=', 'wiki_revisions.wiki_page_id')
            ->whereNull('wiki_pages.deleted_at')
            ->whereNotNull('wiki_pages.current_revision_id')
            ->orderByDesc('wiki_revisions.id')
            ->limit(self::RECENT_CHANGES)
            ->get(['wiki_revisions.*', 'wiki_pages.slug as page_slug', 'wiki_pages.current_revision_id as page_current_revision_id']);

        $categories = WikiCategory::query()
            ->withCount('pages')
            ->orderByDesc('pages_count')
            ->orderBy('name')
            ->get();

        $pages = WikiPage::query()
            ->live()
            ->with('currentRevision:id,title,description')
            ->get()
            ->sortBy(fn (WikiPage $page): string => (string) $page->currentRevision?->title)
            ->values();

        return new WikiIndexPageData(
            title: (string) config('wiki.title'),
            recentChanges: $recentChanges->map(fn (WikiRevision $revision): WikiRevisionSummaryViewModel => WikiRevisionSummaryViewModel::fromModel(
                $revision,
                (string) $revision->getAttribute('page_slug'),
                (int) $revision->getAttribute('page_current_revision_id'),
            ))->all(),
            categories: $categories->map(fn (WikiCategory $category): WikiCategoryLinkViewModel => WikiCategoryLinkViewModel::fromName(
                $category->name,
                (int) $category->getAttribute('pages_count'),
            ))->all(),
            pages: $pages->map(fn (WikiPage $page): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($page))->all(),
            query: $query,
            searchResults: $query === '' ? null : $this->search($query),
            newPageUrl: route('wiki.new'),
        );
    }

    /**
     * @return list<WikiPageLinkViewModel>
     */
    private function search(string $query): array
    {
        $like = '%'.addcslashes($query, '%_\\').'%';

        return WikiPage::query()
            ->live()
            ->select('wiki_pages.*')
            ->join('wiki_revisions', 'wiki_revisions.id', '=', 'wiki_pages.current_revision_id')
            ->where(fn ($where) => $where
                ->where('wiki_revisions.title', 'like', $like)
                ->orWhere('wiki_revisions.description', 'like', $like)
                ->orWhere('wiki_revisions.body', 'like', $like))
            ->orderByRaw('case when wiki_revisions.title like ? then 0 else 1 end', [$like])
            ->limit(self::SEARCH_RESULTS)
            ->with('currentRevision:id,title,description')
            ->get()
            ->map(fn (WikiPage $page): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($page))
            ->all();
    }
}
