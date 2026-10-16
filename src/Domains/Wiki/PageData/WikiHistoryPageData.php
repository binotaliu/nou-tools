<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;
use Spatie\LaravelData\Resource;

final class WikiHistoryPageData extends Resource
{
    /**
     * @param  list<WikiRevisionSummaryViewModel>  $revisions
     */
    public function __construct(
        public WikiPageLinkViewModel $page,
        public array $revisions,
    ) {}
}
