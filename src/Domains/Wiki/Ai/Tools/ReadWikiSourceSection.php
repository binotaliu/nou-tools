<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Actions\FindWikiSourceDocument;
use Stringable;

/**
 * Reads one section of a stored document. The result names the URL and page
 * to cite, since reading it counts as having read the source.
 */
final readonly class ReadWikiSourceSection implements Tool
{
    public function __construct(private FindWikiSourceDocument $findWikiSourceDocument) {}

    public function name(): string
    {
        return 'read_source_section';
    }

    public function description(): Stringable|string
    {
        return 'Read one section of a stored document by its key (see list_source_sections). Returns its text and the URL with page to cite in a footnote. Omit term for the series\' current document.';
    }

    public function handle(Request $request): Stringable|string
    {
        $series = trim((string) $request->string('series'));
        $term = trim((string) $request->string('term'));
        $key = trim((string) $request->string('key'));
        $document = ($this->findWikiSourceDocument)($series, $term);

        if ($document === null) {
            return "No stored document for series \"{$series}\"".($term === '' ? '' : " and term \"{$term}\"").'. Use list_source_documents to see what exists.';
        }

        $section = $document->sections()->where('key', $key)->first();

        if ($section === null) {
            return "No section \"{$key}\" in this document. Use list_source_sections to see its keys.";
        }

        return implode("\n", [
            "series: {$document->series}",
            'term: '.($document->term ?? '常態'),
            "title: {$document->title}",
            "section: {$section->key} | {$section->title}",
            'pages: '.($section->page_start === $section->page_end ? $section->page_start : "{$section->page_start}-{$section->page_end}"),
            "cite: {$document->url}#page={$section->page_start}",
            '',
            $section->body,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'series' => $schema->string()->description('The document series, e.g. course-selection-notes.')->required(),
            'term' => $schema->string()->description('The semester, e.g. 115-1. Omit for the current document.'),
            'key' => $schema->string()->description('The section key, e.g. part-8 or appendix-10.')->required(),
        ];
    }
}
