<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use Spatie\LaravelData\Resource;

final class WikiGuidePageData extends Resource
{
    public function __construct(
        public string $guide,
        public string $title,
        // Plain HTML string: spatie/laravel-data serialises HtmlString to {}.
        public string $content,
        public string $otherGuideTitle,
        public string $otherGuideUrl,
    ) {}
}
