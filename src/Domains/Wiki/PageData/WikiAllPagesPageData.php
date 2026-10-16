<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use Spatie\LaravelData\Resource;

/**
 * 所有條目: every page, by title.
 */
final class WikiAllPagesPageData extends Resource
{
    /**
     * @param  list<WikiPageLinkViewModel>  $pages
     */
    public function __construct(
        public string $wikiTitle,
        public array $pages,
        public string $newPageUrl,
    ) {}
}
