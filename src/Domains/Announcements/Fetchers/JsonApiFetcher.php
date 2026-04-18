<?php

namespace NouTools\Domains\Announcements\Fetchers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Contracts\AnnouncementFetcher;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\DataTransferObjects\FetchedAnnouncementDTO;

final readonly class JsonApiFetcher implements AnnouncementFetcher
{
    /**
     * @return Collection<int, FetchedAnnouncementDTO>
     */
    public function fetch(AnnouncementSourceConfigDTO $source): Collection
    {
        $response = Http::timeout(30)->get($source->fetchUrl);
        $response->throw();

        $data = $response->json();
        $baseUrl = $source->fetcherConfig['base_url'] ?? '';

        /** @var array<int, array{AdvertID: int, Title: string, Url: ?string, File: ?string, Tags: ?string, StartDateTime: ?string}> $adverts */
        $adverts = $data['Adverts'] ?? [];

        return collect($adverts)->map(function (array $advert) use ($baseUrl): FetchedAnnouncementDTO {
            $relativePath = $advert['Url'] ?? $advert['File'] ?? '';
            $url = $relativePath !== '' ? rtrim($baseUrl, '/').'/'.ltrim($relativePath, '/') : '';

            $tags = null;
            if (! empty($advert['Tags'])) {
                $tags = array_map('trim', explode(',', $advert['Tags']));
            }

            $publishedAt = null;
            if (! empty($advert['CreateDateTime'])) {
                $publishedAt = CarbonImmutable::parse($advert['CreateDateTime'], 'Asia/Taipei');
            }
            if (! empty($advert['StartDateTime'])) {
                $publishedAt = CarbonImmutable::parse($advert['StartDateTime'], 'Asia/Taipei');
            }

            return new FetchedAnnouncementDTO(
                sourceId: (string) $advert['AdvertID'],
                title: $advert['Title'],
                url: $url,
                tags: $tags,
                publishedAt: $publishedAt,
            );
        });
    }
}
