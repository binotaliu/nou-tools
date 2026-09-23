<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\PageData;

use NouTools\Domains\Changelog\ViewModels\ChangelogPostSummaryViewModel;
use NouTools\Domains\Changelog\ViewModels\ChangelogPostViewModel;
use Spatie\LaravelData\Resource;

final class ChangelogPostPageData extends Resource
{
    public function __construct(
        public string $title,
        public ChangelogPostViewModel $post,
        public ?ChangelogPostSummaryViewModel $previousPost,
        public ?ChangelogPostSummaryViewModel $nextPost,
    ) {}
}
