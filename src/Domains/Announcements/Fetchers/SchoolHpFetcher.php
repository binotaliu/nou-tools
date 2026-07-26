<?php

declare(strict_types=1);

namespace NouTools\Domains\Announcements\Fetchers;

use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
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
        $tabs = $xpath->query('//div[starts-with(@id, "cph_Content_rt_Content_dv_Name_")]');

        if ($tabs === false || $tabs->length === 0) {
            return collect();
        }

        $results = [];

        foreach ($tabs as $tab) {
            if (! $tab instanceof DOMElement) {
                continue;
            }

            $tabId = $tab->getAttribute('id');
            if (! preg_match('/^cph_Content_rt_Content_dv_Name_([0-6])$/', $tabId)) {
                continue;
            }

            $items = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " list-group-item ") and @href]', $tab);
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
        $href = trim($item->getAttribute('href'));
        if ($href === '') {
            return null;
        }

        $text = $this->extractPlainTextWithoutTagSpans($item, $xpath);
        if ($text === '') {
            return null;
        }

        if (! preg_match('/^(\d{4}\/\d{2}\/\d{2})\s*(.+)$/u', $text, $matches)) {
            return null;
        }

        $dateText = $matches[1];
        $title = trim($matches[2]);
        if ($title === '') {
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

    private function extractPlainTextWithoutTagSpans(DOMElement $item, DOMXPath $xpath): string
    {
        $clone = $item->cloneNode(true);
        if (! $clone instanceof DOMNode) {
            return '';
        }

        $tagNodes = $xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " w3-tag ")]', $clone);
        if ($tagNodes !== false) {
            for ($index = $tagNodes->length - 1; $index >= 0; $index--) {
                $node = $tagNodes->item($index);
                if ($node !== null && $node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $text = preg_replace('/\s+/u', ' ', trim($clone->textContent ?? '')) ?? '';

        return trim($text);
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
