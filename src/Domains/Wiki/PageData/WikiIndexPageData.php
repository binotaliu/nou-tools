<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiCategoryLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;
use Spatie\LaravelData\Resource;

final class WikiIndexPageData extends Resource
{
    /**
     * @param  list<WikiRevisionSummaryViewModel>  $recentChanges
     * @param  list<WikiCategoryLinkViewModel>  $categories
     * @param  list<WikiPageLinkViewModel>  $pages
     * @param  list<WikiPageLinkViewModel>|null  $searchResults  null when nothing was searched
     */
    public function __construct(
        public string $title,
        public array $recentChanges,
        public array $categories,
        public array $pages,
        public string $query,
        public ?array $searchResults,
        public string $newPageUrl,
    ) {}
}
