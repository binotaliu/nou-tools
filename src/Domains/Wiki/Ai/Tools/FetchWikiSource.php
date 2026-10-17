<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Exceptions\WikiSourceFetchException;
use NouTools\Domains\Wiki\Support\FetchWikiSourceText;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use Stringable;
use Throwable;

/**
 * Our own `web_fetch` for providers that have no server-side fetch tool
 * (OpenAI). The download and text extraction are {@see FetchWikiSourceText};
 * this adds the per-draft fetch cap and a character cap on what the model gets.
 */
final class FetchWikiSource implements Tool
{
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
        } catch (WikiSourceFetchException $exception) {
            return $exception->getMessage();
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
        $source = app(FetchWikiSourceText::class)($url, self::MAX_DOWNLOAD_BYTES);

        return "url: {$source->url}\n\n".Str::limit($source->text, self::MAX_TEXT_CHARS, "\n[truncated]");
    }
}
