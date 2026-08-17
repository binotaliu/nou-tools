<?php

declare(strict_types=1);

namespace NouTools\Domains\Announcements\Fetchers;

use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Contracts\AnnouncementFetcher;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\DataTransferObjects\FetchedAnnouncementDTO;

final readonly class SchoolHpFetcher implements AnnouncementFetcher
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
        $tabs = $xpath->query('//ul[starts-with(@id, "tab-")]');

        if ($tabs === false || $tabs->length === 0) {
            return collect();
        }

        $results = [];

        foreach ($tabs as $tab) {
            if (! $tab instanceof DOMElement) {
                continue;
            }

            $items = $xpath->query('.//li', $tab);
            if ($items === false || $items->length === 0) {
                continue;
            }

            foreach ($items as $item) {
                if (! $item instanceof DOMElement) {
                    continue;
                }

                $parsed = $this->parseItem($item, $xpath, $baseUrl);
                if ($parsed === null) {
                    continue;
                }

                $results[] = $parsed;
            }
        }

        return collect($results);
    }

    private function parseItem(DOMElement $item, DOMXPath $xpath, string $baseUrl): ?FetchedAnnouncementDTO
    {
        $link = $xpath->query('.//a[@href]', $item)?->item(0);
        if (! $link instanceof DOMElement) {
            return null;
        }

        $href = trim($link->getAttribute('href'));
        if ($href === '') {
            return null;
        }

        $title = trim($link->getAttribute('title'));
        if ($title === '') {
            $title = preg_replace('/\s+/u', ' ', trim($link->textContent ?? '')) ?? '';
            $title = trim($title);
        }
        if ($title === '') {
            return null;
        }

        $dateNode = $xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " font-mono ")]', $item)?->item(0);
        $dateText = $dateNode !== null ? trim($dateNode->textContent ?? '') : '';
        if ($dateText === '') {
            return null;
        }

        $publishedAt = $this->parseDate($dateText);

        return new FetchedAnnouncementDTO(
            sourceId: $href,
            title: $title,
            url: $this->resolveUrl($href, $baseUrl),
            tags: null,
            publishedAt: $publishedAt,
        );
    }

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($href, '/');
    }

    private function parseDate(string $dateText): ?CarbonInterface
    {
        try {
            $parsed = Date::createFromFormat('Y/m/d', $dateText, 'Asia/Taipei');

            return $parsed === false ? null : $parsed;
        } catch (\Throwable) {
            return null;
        }
    }
}
