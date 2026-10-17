<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use App\Models\WikiSourceSection;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Actions\FindWikiSourceDocument;
use Stringable;

/**
 * The table of contents of a stored document: key, title, pages and size of
 * each section, so the editor reads only the ones a request touches.
 */
final readonly class ListWikiSourceSections implements Tool
{
    public function __construct(private FindWikiSourceDocument $findWikiSourceDocument) {}

    public function name(): string
    {
        return 'list_source_sections';
    }

    public function description(): Stringable|string
    {
        return 'List the sections of a stored document as "key | title | pages | characters" lines. Omit term for the series\' current document.';
    }

    public function handle(Request $request): Stringable|string
    {
        $series = trim((string) $request->string('series'));
        $term = trim((string) $request->string('term'));
        $document = ($this->findWikiSourceDocument)($series, $term);

        if ($document === null) {
            return "No stored document for series \"{$series}\"".($term === '' ? '' : " and term \"{$term}\"").'. Use list_source_documents to see what exists.';
        }

        return implode("\n", [
            "series: {$document->series}",
            'term: '.($document->term ?? '常態'),
            "title: {$document->title}",
            "url: {$document->url}",
            '',
            ...$document->sections()
                ->get(['key', 'title', 'page_start', 'page_end', 'body'])
                ->map(fn (WikiSourceSection $section): string => implode(' | ', [
                    $section->key,
                    $section->title,
                    $section->page_start === $section->page_end ? "p{$section->page_start}" : "p{$section->page_start}-{$section->page_end}",
                    mb_strlen($section->body).' chars',
                ]))
                ->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'series' => $schema->string()->description('The document series, e.g. course-selection-notes.')->required(),
            'term' => $schema->string()->description('The semester, e.g. 115-1. Omit for the current document.'),
        ];
    }
}
