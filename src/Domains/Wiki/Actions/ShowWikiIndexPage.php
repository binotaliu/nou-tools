<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiCategory;
use App\Models\WikiLink;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use App\Models\WikiSlugRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use NouTools\Domains\Wiki\PageData\WikiIndexPageData;
use NouTools\Domains\Wiki\ViewModels\WikiCategoryLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiCategoryPortalViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiFeaturedPageViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiWantedPageViewModel;

/**
 * The wiki's homepage, after Wikipedia's: an intro admins edit (the
 * protected `main-page` page), then sections that follow the wiki on their
 * own: the day's featured page, categories with a few pages each, recent
 * changes, new pages and pages that are linked to but not written yet.
 */
final readonly class ShowWikiIndexPage
{
    private const int RECENT_CHANGES = 8;

    private const int NEW_PAGES = 5;

    private const int WANTED_PAGES = 8;

    private const int PAGES_PER_CATEGORY = 5;

    private const int SEARCH_RESULTS = 30;

    private const int EXCERPT_LENGTH = 160;

    public function __construct(private RenderWikiMarkdown $renderWikiMarkdown) {}

    public function __invoke(string $query = ''): WikiIndexPageData
    {
        $query = mb_substr(trim($query), 0, 100);

        $mainPage = WikiPage::query()
            ->live()
            ->where('slug', WikiPage::MAIN_PAGE_SLUG)
            ->with('currentRevision')
            ->first();

        return new WikiIndexPageData(
            title: (string) config('wiki.title'),
            intro: $mainPage !== null ? ($this->renderWikiMarkdown)($mainPage->currentRevision?->body) : null,
            introHistoryUrl: $mainPage !== null ? route('wiki.history', $mainPage->slug) : null,
            pageCount: WikiPage::query()->listed()->count(),
            featured: $this->featured(),
            categories: $this->categories(),
            recentChanges: $this->recentChanges(),
            newPages: WikiPage::query()
                ->listed()
                ->latest('created_at')
                ->latest('id')
                ->limit(self::NEW_PAGES)
                ->with('currentRevision:id,title,description')
                ->get()
                ->map(fn (WikiPage $page): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($page))
                ->all(),
            wantedPages: $this->wantedPages(),
            query: $query,
            searchResults: $query === '' ? null : $this->search($query),
            newPageUrl: route('wiki.new'),
            allPagesUrl: route('wiki.all'),
        );
    }

    /**
     * One page a day, the same for everyone that (Taipei) day: the pages
     * marked 精選 take turns, and while none is marked every page does.
     */
    private function featured(): ?WikiFeaturedPageViewModel
    {
        $candidates = fn (bool $featuredOnly): Builder => WikiPage::query()
            ->listed()
            ->when($featuredOnly, fn (Builder $where) => $where->where('is_featured', true));

        $isFeatured = $candidates(true)->exists();
        $count = $candidates($isFeatured)->count();

        if ($count === 0) {
            return null;
        }

        $taipeiNow = now('Asia/Taipei');
        $day = intdiv($taipeiNow->getTimestamp() + $taipeiNow->getOffset(), 86400);

        /** @var WikiPage $page */
        $page = $candidates($isFeatured)
            ->orderBy('id')
            ->skip($day % $count)
            ->with('currentRevision')
            ->firstOrFail();

        return new WikiFeaturedPageViewModel(
            page: WikiPageLinkViewModel::fromModel($page),
            excerpt: $this->excerpt((string) $page->currentRevision?->body),
            categories: array_map(
                fn (string $name): WikiCategoryLinkViewModel => WikiCategoryLinkViewModel::fromName($name),
                $page->currentRevision->categories ?? [],
            ),
            isFeatured: $isFeatured,
        );
    }

    /**
     * The first paragraph as plain text, footnote markers dropped.
     */
    private function excerpt(string $body): ?string
    {
        $html = ($this->renderWikiMarkdown)($body);

        if (preg_match('/<p>(.*?)<\/p>/su', $html, $match) !== 1) {
            return null;
        }

        $text = html_entity_decode(strip_tags((string) preg_replace('/<sup\b.*?<\/sup>/su', '', $match[1])), ENT_QUOTES | ENT_HTML5);
        $text = Str::squish($text);

        return $text === '' ? null : Str::limit($text, self::EXCERPT_LENGTH, '…', preserveWords: false);
    }

    /**
     * @return list<WikiCategoryPortalViewModel>
     */
    private function categories(): array
    {
        /** @var Collection<int, WikiCategory> $categories */
        $categories = WikiCategory::query()
            ->with(['pages' => fn ($pages) => $pages->listed()->with('currentRevision:id,title,description')])
            ->get();

        return $categories
            ->filter(fn (WikiCategory $category): bool => $category->pages->isNotEmpty())
            ->sortBy([
                fn (WikiCategory $a, WikiCategory $b): int => $b->pages->count() <=> $a->pages->count(),
                fn (WikiCategory $a, WikiCategory $b): int => $a->name <=> $b->name,
            ])
            ->map(fn (WikiCategory $category): WikiCategoryPortalViewModel => new WikiCategoryPortalViewModel(
                name: $category->name,
                url: route('wiki.category', ['category' => $category->name]),
                pageCount: $category->pages->count(),
                pages: $category->pages
                    ->sortBy(fn (WikiPage $page): string => (string) $page->currentRevision?->title)
                    ->take(self::PAGES_PER_CATEGORY)
                    ->map(fn (WikiPage $page): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($page))
                    ->values()
                    ->all(),
            ))
            ->values()
            ->all();
    }

    /**
     * @return list<WikiRevisionSummaryViewModel>
     */
    private function recentChanges(): array
    {
        return WikiRevision::query()
            ->join('wiki_pages', 'wiki_pages.id', '=', 'wiki_revisions.wiki_page_id')
            ->whereNull('wiki_pages.deleted_at')
            ->whereNotNull('wiki_pages.current_revision_id')
            ->where('wiki_pages.slug', '!=', WikiPage::MAIN_PAGE_SLUG)
            ->orderByDesc('wiki_revisions.id')
            ->limit(self::RECENT_CHANGES)
            ->with('studentSchedule.studyRoomProfile:id,student_schedule_id,nickname')
            ->get(['wiki_revisions.*', 'wiki_pages.slug as page_slug', 'wiki_pages.current_revision_id as page_current_revision_id'])
            ->map(fn (WikiRevision $revision): WikiRevisionSummaryViewModel => WikiRevisionSummaryViewModel::fromModel(
                $revision,
                (string) $revision->getAttribute('page_slug'),
                (int) $revision->getAttribute('page_current_revision_id'),
            ))
            ->all();
    }

    /**
     * Red links, most linked first: slugs live pages link to that are
     * neither a page (deleted ones may come back) nor a renamed page's old
     * slug.
     *
     * @return list<WikiWantedPageViewModel>
     */
    private function wantedPages(): array
    {
        return WikiLink::query()
            ->selectRaw('to_slug, count(*) as link_count')
            ->whereIn('from_page_id', WikiPage::query()->live()->select('id'))
            ->whereNotIn('to_slug', WikiPage::withTrashed()->select('slug'))
            ->whereNotIn('to_slug', WikiSlugRedirect::query()->select('old_slug'))
            ->groupBy('to_slug')
            ->orderByDesc('link_count')
            ->orderBy('to_slug')
            ->limit(self::WANTED_PAGES)
            ->get()
            ->map(fn (WikiLink $link): WikiWantedPageViewModel => new WikiWantedPageViewModel(
                slug: $link->to_slug,
                linkCount: (int) $link->getAttribute('link_count'),
                createUrl: route('wiki.new', ['slug' => $link->to_slug]),
            ))
            ->all();
    }

    /**
     * @return list<WikiPageLinkViewModel>
     */
    private function search(string $query): array
    {
        $like = '%'.addcslashes($query, '%_\\').'%';

        return WikiPage::query()
            ->listed()
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
