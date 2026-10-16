<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Models\User;
use App\Models\WikiEditRequest;
use DomainException;

final readonly class RejectWikiEditRequest
{
    /**
     * @throws DomainException when the request isn't pending
     */
    public function __invoke(WikiEditRequest $editRequest, User $reviewer, ?string $note): WikiEditRequest
    {
        if ($editRequest->status !== WikiEditRequestStatus::PendingReview) {
            throw new DomainException('這個修改不在待審核狀態。');
        }

        $editRequest->status = WikiEditRequestStatus::Rejected;
        $editRequest->review_note = $note;
        $editRequest->reviewed_by = $reviewer->id;
        $editRequest->reviewed_at = now();
        $editRequest->saveOrFail();

        return $editRequest;
    }
}
