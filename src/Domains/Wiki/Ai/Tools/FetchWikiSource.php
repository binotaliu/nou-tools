<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use Stringable;
use Throwable;

/**
 * Our own `web_fetch` for providers that have no server-side fetch tool
 * (OpenAI). Only hosts in `wiki.source_domains` are fetched, every redirect
 * hop is checked again, and the number of fetches per draft is capped. HTML
 * becomes plain text and PDFs go through `pdftotext`.
 */
final class FetchWikiSource implements Tool
{
    private const MAX_REDIRECTS = 4;

    private const MAX_DOWNLOAD_BYTES = 8_000_000;

    private const MAX_TEXT_CHARS = 40_000;

    private int $fetches = 0;

    public function __construct(private readonly int $maxFetches) {}

    public function name(): string
    {
        return 'web_fetch';
    }

    public function description(): Stringable|string
    {
        return 'Fetch a web page or PDF from an allowed school/government site and return its text. Only hosts the submitter may cite are allowed: '
            .implode(', ', WikiSourceDomains::all()).". At most {$this->maxFetches} fetches per draft.";
    }

    public function handle(Request $request): Stringable|string
    {
        $url = trim((string) $request->string('url'));

        if (! WikiSourceDomains::allows($url)) {
            return 'Refused: this URL is not on an allowed host.';
        }

        if ($this->fetches >= $this->maxFetches) {
            return 'Refused: the fetch limit for this draft is used up.';
        }

        $this->fetches++;

        try {
            return $this->fetch($url);
        } catch (Throwable $exception) {
            return 'Could not fetch the page: '.Str::limit($exception->getMessage(), 200);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('The full http(s) URL to read.')->required(),
        ];
    }

    private function fetch(string $url): string
    {
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
                return 'Refused: the page redirects to a host that is not allowed.';
            }
        }

        if ($response === null || $response->redirect() || ! $response->successful()) {
            return 'Could not fetch the page: HTTP '.($response?->status() ?? 'error');
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_DOWNLOAD_BYTES) {
            return 'Refused: the file is too large.';
        }

        $text = str_contains(strtolower($response->header('Content-Type')), 'pdf') || str_starts_with($body, '%PDF')
            ? $this->pdfText($body)
            : $this->htmlText($response);

        return "url: {$url}\n\n".Str::limit($text, self::MAX_TEXT_CHARS, "\n[truncated]");
    }

    private function pdfText(string $pdf): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wikipdf');
        file_put_contents($path, $pdf);

        try {
            $result = Process::timeout(30)->run(['pdftotext', '-layout', '-enc', 'UTF-8', $path, '-']);
        } finally {
            @unlink($path);
        }

        return $result->successful() && trim($result->output()) !== ''
            ? trim($result->output())
            : '[This PDF has no extractable text, or PDF text extraction is not available.]';
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
