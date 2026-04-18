<?php

namespace NouTools\Domains\Announcements\Fetchers;

use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Contracts\AnnouncementFetcher;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\DataTransferObjects\FetchedAnnouncementDTO;

final readonly class HtmlScrapeFetcher implements AnnouncementFetcher
{
    /**
     * @return Collection<int, FetchedAnnouncementDTO>
     */
    public function fetch(AnnouncementSourceConfigDTO $source): Collection
    {
        $response = Http::timeout(30)->get($source->fetchUrl);
        $response->throw();

        $html = $response->body();
        $baseUrl = $source->fetcherConfig['base_url'] ?? '';

        return $this->parseHtml($html, $baseUrl);
    }

    /**
     * @return Collection<int, FetchedAnnouncementDTO>
     */
    private function parseHtml(string $html, string $baseUrl): Collection
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $items = $xpath->query('//ul[contains(@class, "page-list-cont")]/li');

        if ($items === false || $items->length === 0) {
            return collect();
        }

        $results = [];

        foreach ($items as $item) {
            $linkNode = $xpath->query('.//div[contains(@class, "page-list-info")]/a', $item)?->item(0);
            if ($linkNode === null) {
                continue;
            }

            $href = $linkNode->getAttribute('href');
            if ($href === '') {
                continue;
            }

            $dateNode = $xpath->query('.//div[contains(@class, "page-list-date")]/span', $item)?->item(0);
            $titleNode = $xpath->query('.//div[contains(@class, "page-list-title")]', $item)?->item(0);

            $title = $titleNode !== null ? trim($titleNode->textContent) : '';
            if ($title === '') {
                continue;
            }

            $publishedAt = null;
            if ($dateNode !== null) {
                $dateText = trim($dateNode->textContent);
                if ($dateText !== '') {
                    $publishedAt = CarbonImmutable::parse($dateText, 'Asia/Taipei');
                }
            }

            $fullUrl = str_starts_with($href, 'http')
                ? $href
                : rtrim($baseUrl, '/').'/'.ltrim($href, '/');

            $results[] = new FetchedAnnouncementDTO(
                sourceId: $href,
                title: $title,
                url: $fullUrl,
                tags: null,
                publishedAt: $publishedAt,
            );
        }

        return collect($results);
    }
}
