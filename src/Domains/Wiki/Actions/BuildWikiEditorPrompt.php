<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiEditRequest;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;

/**
 * Builds the editor's user message as tagged plain-text blocks. URLs sit
 * alone on their lines: inside JSON the newsletter's model sliced them badly.
 */
final readonly class BuildWikiEditorPrompt
{
    public function __invoke(WikiEditRequest $editRequest): string
    {
        $blocks = [$this->block('task', $editRequest->isNewPage() ? 'create' : 'edit')];

        $base = $editRequest->baseRevision;

        if ($base !== null && $editRequest->page !== null) {
            $blocks[] = $this->block('page', implode("\n", [
                "slug: {$editRequest->page->slug}",
                "title: {$base->title}",
                'description: '.(string) $base->description,
                'categories: '.implode(', ', $base->categories),
                '',
                $this->block('body', $base->body),
            ]));
        }

        if ($editRequest->isNewPage() && (filled($editRequest->requested_slug) || filled($editRequest->requested_title))) {
            $blocks[] = $this->block('new_page_hints', implode("\n", array_filter([
                filled($editRequest->requested_slug) ? "slug: {$editRequest->requested_slug}" : null,
                filled($editRequest->requested_title) ? "title: {$editRequest->requested_title}" : null,
            ])));
        }

        $blocks[] = $this->block('request', $editRequest->instruction);

        if ($editRequest->refinements !== [] && filled($editRequest->draft_body)) {
            $blocks[] = $this->block('current_draft', implode("\n", [
                'slug: '.(string) $editRequest->draft_slug,
                'title: '.(string) $editRequest->draft_title,
                'description: '.(string) $editRequest->draft_description,
                'categories: '.implode(', ', $editRequest->draft_categories ?? []),
                '',
                $this->block('body', (string) $editRequest->draft_body),
            ]));

            $blocks[] = $this->block('refinements', collect($editRequest->refinements)
                ->map(fn (string $refinement, int $index): string => ($index + 1).'. '.$refinement)
                ->implode("\n"));
        }

        $blocks[] = $this->block('sources', $editRequest->source_urls === []
            ? '(none)'
            : implode("\n", $editRequest->source_urls));

        $documents = $this->documentsCatalog($editRequest);

        if ($documents !== null) {
            $blocks[] = $this->block('documents', $documents);
        }

        if (($editRequest->screenshots ?? []) !== []) {
            $blocks[] = $this->block('screenshots', collect($editRequest->screenshots)
                ->map(fn (array $screenshot, int $index): string => 'Image '.($index + 1).': '.$screenshot['name'])
                ->implode("\n"));
        }

        return implode("\n\n", $blocks);
    }

    /**
     * The stored school documents the editor can read by section. Current
     * documents list their sections; older ones only say they exist. URLs sit
     * alone on their lines, and a document the visitor cited is marked.
     */
    private function documentsCatalog(WikiEditRequest $editRequest): ?string
    {
        $documents = WikiSourceDocument::query()
            ->where('status', WikiSourceDocumentStatus::Ready)
            ->with(['sections' => fn ($sections) => $sections->select(['id', 'wiki_source_document_id', 'key', 'title', 'page_start', 'page_end', 'position'])])
            ->orderBy('series')
            ->orderByDesc('term')
            ->get();

        if ($documents->isEmpty()) {
            return null;
        }

        $currentIds = WikiSourceDocument::query()->current()->pluck('id')->all();

        return $documents
            ->map(function (WikiSourceDocument $document) use ($currentIds, $editRequest): string {
                $isCurrent = in_array($document->id, $currentIds, true);

                return implode("\n", array_filter([
                    implode(' | ', [$document->series, $document->term ?? '常態', $isCurrent ? 'current' : 'older', $document->title]),
                    $document->url,
                    in_array($document->url, $editRequest->source_urls, true) ? 'cited by the visitor' : null,
                    $isCurrent ? 'sections:' : 'sections: see list_source_sections',
                    ...($isCurrent
                        ? $document->sections->map(fn (WikiSourceSection $section): string => "- {$section->key} | {$section->title} | ".($section->page_start === $section->page_end ? "p{$section->page_start}" : "p{$section->page_start}-{$section->page_end}"))->all()
                        : []),
                ]));
            })
            ->implode("\n\n");
    }

    private function block(string $tag, string $content): string
    {
        return "<{$tag}>\n".trim($content)."\n</{$tag}>";
    }
}
