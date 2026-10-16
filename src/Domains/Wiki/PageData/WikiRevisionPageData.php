<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiDiffLineViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;
use Spatie\LaravelData\Resource;

final class WikiRevisionPageData extends Resource
{
    /**
     * @param  list<string>  $sourceUrls
     * @param  list<WikiDiffLineViewModel>  $diff
     */
    public function __construct(
        public WikiPageLinkViewModel $page,
        public WikiRevisionSummaryViewModel $revision,
        public ?string $instruction,
        public array $sourceUrls,
        // Plain HTML string: spatie/laravel-data serialises HtmlString to {}.
        public string $content,
        public array $diff,
        public bool $isFirstRevision,
    ) {}
}
