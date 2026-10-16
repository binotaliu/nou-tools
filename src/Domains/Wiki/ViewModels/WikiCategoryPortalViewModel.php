<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use Spatie\LaravelData\Data;

/**
 * A category on the wiki's homepage, with a few of its pages to start from.
 */
final class WikiCategoryPortalViewModel extends Data
{
    /**
     * @param  list<WikiPageLinkViewModel>  $pages
     */
    public function __construct(
        public string $name,
        public string $url,
        public int $pageCount,
        public array $pages,
    ) {}
}
