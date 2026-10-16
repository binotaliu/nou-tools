<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use Spatie\LaravelData\Data;

/**
 * 今日精選 on the wiki's homepage.
 */
final class WikiFeaturedPageViewModel extends Data
{
    /**
     * @param  list<WikiCategoryLinkViewModel>  $categories
     */
    public function __construct(
        public WikiPageLinkViewModel $page,
        public ?string $excerpt,
        public array $categories,
        // False when no page is marked 精選 and any page stands in.
        public bool $isFeatured,
    ) {}
}
