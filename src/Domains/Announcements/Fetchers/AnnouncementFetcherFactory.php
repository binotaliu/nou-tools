<?php

namespace NouTools\Domains\Announcements\Fetchers;

use App\Enums\AnnouncementFetcherType;
use NouTools\Domains\Announcements\Contracts\AnnouncementFetcher;

final readonly class AnnouncementFetcherFactory
{
    public function make(AnnouncementFetcherType $type): AnnouncementFetcher
    {
        return match ($type) {
            AnnouncementFetcherType::JSON_API => new JsonApiFetcher,
            AnnouncementFetcherType::HTML_SCRAPE => new HtmlScrapeFetcher,
            AnnouncementFetcherType::HTML_NEWS_BOX => new HtmlNewsBoxFetcher,
        };
    }
}
