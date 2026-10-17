<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditEventType;
use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use DomainException;

final readonly class AbandonWikiEditRequest
{
    public function __construct(private RecordWikiEditEvent $recordWikiEditEvent) {}

    /**
     * @throws DomainException
     */
    public function __invoke(WikiEditRequest $editRequest): WikiEditRequest
    {
        if (! $editRequest->canBeAbandoned()) {
            throw new DomainException('這個修改目前無法放棄。');
        }

        $editRequest->status = WikiEditRequestStatus::Abandoned;
        $editRequest->saveOrFail();

        ($this->recordWikiEditEvent)(WikiEditEventType::Abandoned, $editRequest);

        return $editRequest;
    }
}
