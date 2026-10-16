<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use NouTools\Domains\Wiki\Actions\RenderWikiMarkdown;
use Spatie\LaravelData\Data;

/**
 * The status page of a request: what was asked, the draft with its diff and
 * preview, and what the visitor can still do with it.
 */
final class WikiEditRequestViewModel extends Data
{
    /**
     * @param  list<string>  $sourceUrls
     * @param  list<string>  $refinements
     * @param  list<string>  $draftCategories
     * @param  list<WikiDiffLineViewModel>  $diff
     * @param  list<string>  $reviewReasons
     */
    public function __construct(
        public string $uuid,
        public string $status,
        public string $statusLabel,
        public bool $isNewPage,
        public ?WikiPageLinkViewModel $page,
        public string $instruction,
        public array $sourceUrls,
        public array $refinements,
        public int $refinementsLeft,
        public ?string $draftSlug,
        public ?string $draftTitle,
        public ?string $draftDescription,
        public array $draftCategories,
        public ?string $draftEditSummary,
        // Plain HTML string: spatie/laravel-data serialises HtmlString to {}.
        public ?string $draftContent,
        public array $diff,
        public ?string $declineReason,
        public array $reviewReasons,
        public ?string $reviewNote,
        public bool $lastRefinementFailed,
        public ?string $publishedUrl,
        public bool $isOwner,
        public string $createdAt,
    ) {}

    public static function fromModel(WikiEditRequest $editRequest, RenderWikiMarkdown $render, bool $isOwner): self
    {
        $hasDraft = filled($editRequest->draft_body);
        $publishedPage = $editRequest->status === WikiEditRequestStatus::Published ? $editRequest->page : null;

        return new self(
            uuid: $editRequest->uuid,
            status: $editRequest->status->value,
            statusLabel: $editRequest->status->label(),
            isNewPage: $editRequest->isNewPage() && $editRequest->status !== WikiEditRequestStatus::Published,
            page: $editRequest->page?->currentRevision !== null ? WikiPageLinkViewModel::fromModel($editRequest->page) : null,
            instruction: $editRequest->instruction,
            sourceUrls: $editRequest->source_urls,
            refinements: $editRequest->refinements,
            refinementsLeft: max(0, (int) config('wiki.max_refinements') - count($editRequest->refinements)),
            draftSlug: $editRequest->draft_slug,
            draftTitle: $editRequest->draft_title,
            draftDescription: $editRequest->draft_description,
            draftCategories: $editRequest->draft_categories ?? [],
            draftEditSummary: $editRequest->draft_edit_summary,
            draftContent: $hasDraft ? $render($editRequest->draft_body) : null,
            diff: $hasDraft ? WikiDiffLineViewModel::between((string) $editRequest->baseRevision?->body, (string) $editRequest->draft_body) : [],
            declineReason: $editRequest->declineReason(),
            reviewReasons: $editRequest->ai_verdict['reviewReasons'] ?? [],
            reviewNote: $editRequest->review_note,
            lastRefinementFailed: (bool) ($editRequest->ai_verdict['lastRefinementFailed'] ?? false),
            publishedUrl: $publishedPage !== null ? route('wiki.show', $publishedPage->slug) : null,
            isOwner: $isOwner,
            createdAt: $editRequest->created_at->toIso8601String(),
        );
    }
}
