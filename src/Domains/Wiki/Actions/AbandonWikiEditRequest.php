<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use DomainException;

final readonly class AbandonWikiEditRequest
{
    /**
     * @throws DomainException
     */
    public function __invoke(WikiEditRequest $editRequest): WikiEditRequest
    {
        if (! in_array($editRequest->status, [WikiEditRequestStatus::AwaitingConfirmation, WikiEditRequestStatus::Conflict, WikiEditRequestStatus::Failed, WikiEditRequestStatus::DeclinedByAi], true)) {
            throw new DomainException('這個修改目前無法放棄。');
        }

        $editRequest->status = WikiEditRequestStatus::Abandoned;
        $editRequest->saveOrFail();

        return $editRequest;
    }
}
