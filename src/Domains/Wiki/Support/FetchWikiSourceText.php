<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use NouTools\Domains\Wiki\Exceptions\WikiSourceFetchException;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Downloads a page or PDF from an allowed host and returns its text. Only
 * hosts in `wiki.source_domains` are fetched and every redirect hop is
 * checked again. HTML becomes plain text and PDFs go through `pdftotext`.
 * Shared by the editor's `web_fetch` tool and the source document import.
 */
final class FetchWikiSourceText
{
    private const MAX_REDIRECTS = 4;

    /**
     * @throws WikiSourceFetchException
     */
    public function __invoke(string $url, int $maxDownloadBytes): FetchedWikiSource
    {
        if (! WikiSourceDomains::allows($url)) {
            throw new WikiSourceFetchException('Refused: this URL is not on an allowed host.');
        }

        $response = null;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $response = Http::timeout(30)->withoutRedirecting()->withUserAgent('NouToolsWikiBot/1.0')->get($url);

            if (! $response->redirect()) {
                break;
            }

            $url = (string) Str::of($response->header('Location'))->pipe(
                fn (string $location): string => str_starts_with($location, '/')
                    ? parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).$location
                    : $location,
            );

            if (! WikiSourceDomains::allows($url)) {
                throw new WikiSourceFetchException('Refused: the page redirects to a host that is not allowed.');
            }
        }

        if ($response === null || $response->redirect() || ! $response->successful()) {
            throw new WikiSourceFetchException('Could not fetch the page: HTTP '.($response?->status() ?? 'error'));
        }

        $body = $response->body();

        if (strlen($body) > $maxDownloadBytes) {
            throw new WikiSourceFetchException('Refused: the file is too large.');
        }

        if (str_contains(strtolower($response->header('Content-Type')), 'pdf') || str_starts_with($body, '%PDF')) {
            $text = $this->pdfText($body);

            return new FetchedWikiSource($url, $text, explode("\f", $text));
        }

        $text = $this->htmlText($response);

        return new FetchedWikiSource($url, $text, [$text]);
    }

    private function pdfText(string $pdf): string
    {
        $binary = config('wiki.pdftotext_path')
            ?: (new ExecutableFinder)->find('pdftotext', null, ['/opt/homebrew/bin', '/usr/local/bin', '/usr/bin']);

        if ($binary === null) {
            Log::error('Wiki source fetch: pdftotext was not found, so PDF sources cannot be read. Install poppler or set WIKI_PDFTOTEXT_PATH.');

            throw new WikiSourceFetchException('[This PDF could not be read: the server has no PDF text extractor (pdftotext). Tell the user the PDF could not be read.]');
        }

        // `-` as the input reads the PDF from stdin, so no temporary file.
        $result = Process::timeout(30)->input($pdf)->run([$binary, '-layout', '-enc', 'UTF-8', '-', '-']);

        if (! $result->successful() || trim($result->output()) === '') {
            throw new WikiSourceFetchException('[This PDF has no extractable text (it may be a scan).]');
        }

        return trim($result->output());
    }

    private function htmlText(Response $response): string
    {
        $html = $response->body();

        if (! mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'BIG5,GBK,ISO-8859-1');
        }

        $html = preg_replace('#<(script|style|noscript|svg|nav|footer)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $html = preg_replace('#<(br|/p|/div|/li|/tr|/h[1-6])\b[^>]*>#i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace(["/[ \t\x{00A0}\x{3000}]+/u", "/\s*\n\s*/"], [' ', "\n"], $text) ?? $text;

        return trim($text);
    }
}
