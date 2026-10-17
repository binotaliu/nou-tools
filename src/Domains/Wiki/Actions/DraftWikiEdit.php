<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Image;
use NouTools\Domains\Wiki\Ai\WikiEditor;
use NouTools\Domains\Wiki\Ai\WikiEditorDraft;
use NouTools\Domains\Wiki\Exceptions\InvalidWikiDraftException;
use Throwable;

/**
 * Runs the AI editor on a request that is drafting and records the outcome:
 * a draft ready to submit (DraftAndSubmitWikiEdit sends it on), a decline
 * with its reason, or a failure. The model call happens before anything is written, and a draft
 * that fails validation never replaces the previous one.
 */
final readonly class DraftWikiEdit
{
    public function __construct(
        private BuildWikiEditorPrompt $buildWikiEditorPrompt,
        private ValidateWikiDraft $validateWikiDraft,
    ) {}

    public function __invoke(WikiEditRequest $editRequest): WikiEditRequest
    {
        if ($editRequest->status !== WikiEditRequestStatus::Drafting) {
            return $editRequest;
        }

        $editRequest->loadMissing(['page', 'baseRevision']);

        try {
            $draft = WikiEditorDraft::fromResponse(
                (new WikiEditor)->prompt(($this->buildWikiEditorPrompt)($editRequest), $this->screenshotAttachments($editRequest)),
            );

            if ($draft->applies) {
                ($this->validateWikiDraft)($editRequest, $draft);
            }
        } catch (InvalidWikiDraftException $exception) {
            Log::warning('Wiki draft failed validation.', ['uuid' => $editRequest->uuid, 'problems' => $exception->problems]);

            return $this->fail($editRequest, $exception->problems);
        } catch (Throwable $exception) {
            Log::error('Wiki AI draft failed.', ['uuid' => $editRequest->uuid, 'exception' => $exception->getMessage()]);
            report($exception);

            return $this->fail($editRequest, ['editor error']);
        }

        if (! $draft->applies && $editRequest->refinements !== [] && filled($editRequest->draft_body)) {
            $editRequest->refinements = array_slice($editRequest->refinements, 0, -1);
            $editRequest->ai_verdict = [
                ...($editRequest->ai_verdict ?? []),
                'lastRefinementFailed' => true,
                'lastRefinementDeclineReason' => $draft->declineReason,
            ];
            $editRequest->status = $this->statusBeforeRefinement($editRequest);
            $editRequest->saveOrFail();

            return $editRequest;
        }

        $editRequest->ai_verdict = $draft->verdict();

        if (! $draft->applies) {
            $editRequest->status = WikiEditRequestStatus::DeclinedByAi;
            $editRequest->saveOrFail();

            return $editRequest;
        }

        $editRequest->draft_slug = $editRequest->isNewPage() ? $draft->slug : null;
        $editRequest->draft_title = $draft->title;
        $editRequest->draft_description = $draft->description;
        $editRequest->draft_body = $draft->body;
        $editRequest->draft_categories = $draft->categories;
        $editRequest->draft_edit_summary = $draft->editSummary;
        $editRequest->status = WikiEditRequestStatus::AwaitingConfirmation;
        $editRequest->saveOrFail();

        return $editRequest;
    }

    /**
     * @return list<Image>
     */
    private function screenshotAttachments(WikiEditRequest $editRequest): array
    {
        return array_map(
            fn (array $screenshot): Image => Image::fromStorage($screenshot['path'], WikiEditRequest::SCREENSHOT_DISK),
            $editRequest->screenshots ?? [],
        );
    }

    /**
     * A refinement the editor couldn't carry out leaves the earlier draft
     * where it was: still published, still waiting for review, or (for a
     * request from before drafts were submitted automatically) waiting for
     * the visitor.
     */
    private function statusBeforeRefinement(WikiEditRequest $editRequest): WikiEditRequestStatus
    {
        return WikiEditRequestStatus::tryFrom((string) ($editRequest->ai_verdict['statusBeforeRefinement'] ?? ''))
            ?? WikiEditRequestStatus::AwaitingConfirmation;
    }

    /**
     * A refinement that fails keeps the earlier draft, so the visitor can
     * still submit or try a different wording.
     *
     * @param  list<string>  $problems
     */
    private function fail(WikiEditRequest $editRequest, array $problems): WikiEditRequest
    {
        $editRequest->ai_verdict = [...($editRequest->ai_verdict ?? []), 'problems' => $problems];

        if ($editRequest->refinements !== [] && filled($editRequest->draft_body)) {
            $editRequest->refinements = array_slice($editRequest->refinements, 0, -1);
            $editRequest->status = $this->statusBeforeRefinement($editRequest);
            $editRequest->ai_verdict = [...$editRequest->ai_verdict, 'lastRefinementFailed' => true];
        } else {
            $editRequest->status = WikiEditRequestStatus::Failed;
        }

        $editRequest->saveOrFail();

        return $editRequest;
    }
}
