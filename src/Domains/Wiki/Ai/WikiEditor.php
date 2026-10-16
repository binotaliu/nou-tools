<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Providers\SupportsWebFetch;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebFetch;
use NouTools\Domains\Wiki\Ai\Tools\FetchWikiSource;
use NouTools\Domains\Wiki\Ai\Tools\ListWikiCategories;
use NouTools\Domains\Wiki\Ai\Tools\ReadWikiPage;
use NouTools\Domains\Wiki\Ai\Tools\SearchWikiPages;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use Stringable;

/**
 * Turns a visitor's description of a change (or of a new page) into the
 * complete new page, or declines it. Its rules are `resources/wiki/ai/editor.md`
 * followed by the writing and syntax guides in `resources/wiki/guides/`
 * (editor-only, never served to visitors). It may fetch the cited sources
 * (only on the allowed hosts) and look up other wiki pages and categories.
 */
final class WikiEditor implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return implode("\n\n", [
            trim(File::get(resource_path('wiki/ai/editor.md'))),
            "# Writing guide (撰寫指南)\n\n".trim(File::get(resource_path('wiki/guides/writing-guide.md'))),
            "# Syntax guide (語法說明)\n\n".trim(File::get(resource_path('wiki/guides/syntax-guide.md'))),
        ]);
    }

    /**
     * @return list<Tool|WebFetch>
     */
    public function tools(): iterable
    {
        $tools = [
            app(SearchWikiPages::class),
            app(ReadWikiPage::class),
            app(ListWikiCategories::class),
        ];

        if ($this->maxFetches() > 0) {
            $tools[] = $this->providerFetchesServerSide()
                ? (new WebFetch)->max($this->maxFetches())->allow(WikiSourceDomains::all())
                : new FetchWikiSource($this->maxFetches());
        }

        return $tools;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'decision' => $schema->string()->enum(['apply', 'decline'])->required(),
            'decline_reason' => $schema->string()->required(),
            'slug' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'body' => $schema->string()->required(),
            'categories' => $schema->array()->items($schema->string())->required(),
            'edit_summary' => $schema->string()->required(),
            'risk' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
            'risk_reasons' => $schema->array()->items($schema->string())->required(),
            'sources_used' => $schema->array()->items($schema->string())->required(),
        ];
    }

    public function provider(): string
    {
        return (string) config('wiki.ai.provider');
    }

    public function model(): string
    {
        return (string) config('wiki.ai.model');
    }

    /**
     * Writing a whole page takes a while, and each fetch runs server-side
     * within this one request.
     */
    public function timeout(): int
    {
        return 180 + 30 * $this->maxFetches();
    }

    /**
     * Anthropic and Gemini fetch inside the provider; OpenAI cannot, so it
     * gets our own `FetchWikiSource` tool instead.
     */
    private function providerFetchesServerSide(): bool
    {
        return Ai::textProvider($this->provider()) instanceof SupportsWebFetch;
    }

    private function maxFetches(): int
    {
        return max(0, (int) config('wiki.ai.max_fetches'));
    }
}
