<?php

namespace NouTools\Domains\Announcements\Fetchers;

use Carbon\CarbonImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Contracts\AnnouncementFetcher;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\DataTransferObjects\FetchedAnnouncementDTO;

final readonly class HtmlNewsBoxFetcher implements AnnouncementFetcher
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
        $items = $xpath->query('//div[contains(@class, "cl_news_box")]//div[contains(@class, "clnews_rows")]');

        if ($items === false || $items->length === 0) {
            return collect();
        }

        $results = [];

        foreach ($items as $item) {
            /** @var DOMElement|null $linkNode */
            $linkNode = $xpath->query('.//a[@href]', $item)?->item(0);
            if ($linkNode === null) {
                continue;
            }

            $href = trim($linkNode->getAttribute('href'));
            if ($href === '') {
                continue;
            }

            $title = $this->extractTitle($linkNode->textContent);
            if ($title === '') {
                continue;
            }

            $publishedAt = null;
            $dateNode = $xpath->query('.//p[contains(@class, "text-14")]/span[1]', $item)?->item(0);
            if ($dateNode !== null) {
                $dateText = trim($dateNode->textContent);
                if ($dateText !== '') {
                    $publishedAt = CarbonImmutable::parse($dateText, 'Asia/Taipei');
                }
            }

            $tag = null;
            $tagNode = $xpath->query('.//div[contains(@class, "cl_news_tag")]//p[contains(@class, "text")]', $item)?->item(0);
            if ($tagNode !== null) {
                $tagText = trim($tagNode->textContent);
                if ($tagText !== '') {
                    $tag = [$tagText];
                }
            }

            $fullUrl = str_starts_with($href, 'http')
                ? $href
                : rtrim($baseUrl, '/').'/'.ltrim($href, '/');

            $results[] = new FetchedAnnouncementDTO(
                sourceId: $href,
                title: $title,
                url: $fullUrl,
                tags: $tag,
                publishedAt: $publishedAt,
            );
        }

        return collect($results);
    }

    private function extractTitle(string $rawTitle): string
    {
        $title = preg_replace('/\s+/u', ' ', trim($rawTitle)) ?? '';
        $title = preg_replace('/^(?:(?:Top|New|Hot)\s*)+/u', '', $title) ?? '';

        return trim($title);
    }
}
