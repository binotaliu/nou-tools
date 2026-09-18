<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\PageData;

use Illuminate\Pagination\LengthAwarePaginator;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueSummaryViewModel;
use Spatie\LaravelData\Resource;

final class NewsletterIndexPageData extends Resource
{
    /**
     * @param  LengthAwarePaginator<int, NewsletterIssueSummaryViewModel>  $issues
     */
    public function __construct(
        public string $title,
        public LengthAwarePaginator $issues,
        public string $feedUrl,
    ) {}
}
