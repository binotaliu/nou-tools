<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageViewModel;
use Spatie\LaravelData\Resource;

final class WikiShowPageData extends Resource
{
    /**
     * @param  list<WikiPageLinkViewModel>  $backlinks
     */
    public function __construct(
        public string $wikiTitle,
        public WikiPageViewModel $page,
        public array $backlinks,
    ) {}
}
