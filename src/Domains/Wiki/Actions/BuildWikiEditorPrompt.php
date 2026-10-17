<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiEditRequest;

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

        if (($editRequest->screenshots ?? []) !== []) {
            $blocks[] = $this->block('screenshots', collect($editRequest->screenshots)
                ->map(fn (array $screenshot, int $index): string => 'Image '.($index + 1).': '.$screenshot['name'])
                ->implode("\n"));
        }

        return implode("\n\n", $blocks);
    }

    private function block(string $tag, string $content): string
    {
        return "<{$tag}>\n".trim($content)."\n</{$tag}>";
    }
}
