<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use Spatie\LaravelData\Resource;

final class WikiCategoryPageData extends Resource
{
    /**
     * @param  list<WikiPageLinkViewModel>  $pages
     */
    public function __construct(
        public string $name,
        public array $pages,
    ) {}
}
