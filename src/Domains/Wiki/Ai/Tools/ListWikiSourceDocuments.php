<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the editor see every stored school document, including the older
 * semesters the prompt's catalog leaves without a section list.
 */
final class ListWikiSourceDocuments implements Tool
{
    public function name(): string
    {
        return 'list_source_documents';
    }

    public function description(): Stringable|string
    {
        return 'List the school documents stored for reading (e.g. 選課注意事項 of each semester), as "series | term | current or older | title | section count" lines. Read one with list_source_sections and read_source_section instead of fetching its URL.';
    }

    public function handle(Request $request): Stringable|string
    {
        $currentIds = WikiSourceDocument::query()->current()->pluck('id')->all();

        $documents = WikiSourceDocument::query()
            ->where('status', WikiSourceDocumentStatus::Ready)
            ->withCount('sections')
            ->orderBy('series')
            ->orderByDesc('term')
            ->get();

        if ($documents->isEmpty()) {
            return 'No documents are stored.';
        }

        return $documents
            ->map(fn (WikiSourceDocument $document): string => implode(' | ', [
                $document->series,
                $document->term ?? '常態',
                in_array($document->id, $currentIds, true) ? 'current' : 'older',
                $document->title,
                $document->getAttribute('sections_count').' sections',
            ]))
            ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
