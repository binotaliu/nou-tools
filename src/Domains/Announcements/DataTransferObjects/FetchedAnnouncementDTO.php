<?php

namespace NouTools\Domains\Announcements\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class FetchedAnnouncementDTO extends Data
{
    public function __construct(
        public string $sourceId,
        public string $title,
        public string $url,
        /** @var string[]|null */
        public ?array $tags = null,
        public ?CarbonImmutable $publishedAt = null,
    ) {}
}
