<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiCategory;
use App\Models\WikiPage;
use NouTools\Domains\Wiki\PageData\WikiCategoryPageData;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;

final readonly class ShowWikiCategoryPage
{
    public function __invoke(WikiCategory $category): WikiCategoryPageData
    {
        return new WikiCategoryPageData(
            name: $category->name,
            pages: $category->pages()
                ->listed()
                ->with('currentRevision:id,title,description')
                ->get()
                ->sortBy(fn (WikiPage $page): string => (string) $page->currentRevision?->title)
                ->map(fn (WikiPage $page): WikiPageLinkViewModel => WikiPageLinkViewModel::fromModel($page))
                ->values()
                ->all(),
        );
    }
}
