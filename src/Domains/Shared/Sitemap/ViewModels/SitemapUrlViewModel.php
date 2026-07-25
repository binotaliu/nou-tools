<?php

namespace NouTools\Domains\Shared\Sitemap\ViewModels;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class SitemapUrlViewModel extends Data
{
    public function __construct(
        public string $url,
        public ?CarbonInterface $lastModified = null,
        public ?string $changeFrequency = null,
        public ?float $priority = null,
    ) {}
}
