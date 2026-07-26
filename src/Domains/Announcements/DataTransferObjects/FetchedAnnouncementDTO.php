<?php

declare(strict_types=1);

namespace NouTools\Domains\Announcements\DataTransferObjects;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class FetchedAnnouncementDTO extends Data
{
    public function __construct(
        public string $sourceId,
        public string $title,
        public string $url,
        /** @var string[]|null */
        public ?array $tags = null,
        public ?CarbonInterface $publishedAt = null,
    ) {}
}
