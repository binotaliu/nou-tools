<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\PageData;

use Illuminate\Pagination\LengthAwarePaginator;
use NouTools\Domains\Changelog\ViewModels\ChangelogPostSummaryViewModel;
use Spatie\LaravelData\Resource;

final class ChangelogIndexPageData extends Resource
{
    /**
     * @param  LengthAwarePaginator<int, ChangelogPostSummaryViewModel>  $posts  Past posts, i.e. everything except $latestPost.
     */
    public function __construct(
        public string $title,
        public ?ChangelogPostSummaryViewModel $latestPost,
        public LengthAwarePaginator $posts,
    ) {}
}
