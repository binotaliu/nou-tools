<?php

namespace NouTools\Domains\Announcements\DataTransferObjects;

use App\Enums\AnnouncementFetcherType;
use Spatie\LaravelData\Data;

final class AnnouncementSourceConfigDTO extends Data
{
    /**
     * @param  array<string, mixed>  $fetcherConfig
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $category,
        public string $fetchUrl,
        public AnnouncementFetcherType $fetcherType,
        public array $fetcherConfig = [],
        public bool $tracksExpiry = false,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            name: $config['name'],
            category: $config['category'],
            fetchUrl: $config['fetch_url'],
            fetcherType: AnnouncementFetcherType::from($config['fetcher_type']),
            fetcherConfig: $config['fetcher_config'] ?? [],
            tracksExpiry: (bool) ($config['tracks_expiry'] ?? false),
            isActive: (bool) ($config['is_active'] ?? true),
        );
    }
}
