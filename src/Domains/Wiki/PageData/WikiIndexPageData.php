<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiCategoryPortalViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiFeaturedPageViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiWantedPageViewModel;
use Spatie\LaravelData\Resource;

/**
 * The wiki's homepage: the editable intro (the `main-page` page) on top,
 * then sections that keep themselves up to date.
 */
final class WikiIndexPageData extends Resource
{
    /**
     * @param  list<WikiCategoryPortalViewModel>  $categories
     * @param  list<WikiRevisionSummaryViewModel>  $recentChanges
     * @param  list<WikiPageLinkViewModel>  $newPages
     * @param  list<WikiWantedPageViewModel>  $wantedPages
     * @param  list<WikiPageLinkViewModel>|null  $searchResults  null when nothing was searched
     */
    public function __construct(
        public string $title,
        // Plain HTML string: spatie/laravel-data serialises HtmlString to {}.
        public ?string $intro,
        public ?string $introHistoryUrl,
        public int $pageCount,
        public ?WikiFeaturedPageViewModel $featured,
        public array $categories,
        public array $recentChanges,
        public array $newPages,
        public array $wantedPages,
        public string $query,
        public ?array $searchResults,
        public string $newPageUrl,
        public string $allPagesUrl,
    ) {}
}
