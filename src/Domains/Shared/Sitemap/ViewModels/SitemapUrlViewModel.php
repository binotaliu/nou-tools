<?php

namespace NouTools\Domains\Sitemap\ViewModels;

use Carbon\CarbonInterface;

final readonly class SitemapUrlViewModel
{
    public function __construct(
        public string $url,
        public ?CarbonInterface $lastModified = null,
        public ?string $changeFrequency = null,
        public ?float $priority = null,
    ) {}
}
