<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use DomainException;

/**
 * The public edit flow: the editor drafts and the draft is submitted at
 * once, so a visitor who closes the page before the editor finishes still
 * gets the edit published or queued for review. A refinement the editor
 * could not carry out keeps the earlier draft as it was and is not sent
 * again.
 */
final readonly class DraftAndSubmitWikiEdit
{
    public function __construct(
        private DraftWikiEdit $draftWikiEdit,
        private SubmitWikiEditRequest $submitWikiEditRequest,
    ) {}

    public function __invoke(WikiEditRequest $editRequest): WikiEditRequest
    {
        $editRequest = ($this->draftWikiEdit)($editRequest);

        $isFreshDraft = $editRequest->status === WikiEditRequestStatus::AwaitingConfirmation
            && ! ($editRequest->ai_verdict['lastRefinementFailed'] ?? false);

        if (! $isFreshDraft) {
            return $editRequest;
        }

        try {
            return ($this->submitWikiEditRequest)($editRequest);
        } catch (DomainException) {
            // Someone else moved the request on in the meantime; the status
            // page shows whatever it is now.
            return $editRequest->refresh();
        }
    }
}
