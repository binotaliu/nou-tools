<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Jobs\GenerateWikiEditDraft;
use App\Models\WikiEditRequest;
use DomainException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\DataTransferObjects\RefineWikiEditRequestDTO;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

/**
 * 再調整: the visitor read the draft and asks for one more change to it.
 */
final readonly class RefineWikiEditRequest
{
    public function __construct(
        private EnsureWikiEditingAllowed $ensureWikiEditingAllowed,
        private ConsumeWikiDraftAllowance $consumeWikiDraftAllowance,
    ) {}

    /**
     * @throws DomainException
     * @throws WikiEditingNotAllowedException
     * @throws WikiDraftLimitReachedException
     */
    public function __invoke(WikiEditRequest $editRequest, RefineWikiEditRequestDTO $data, ?StudentScheduleCookie $viewer): WikiEditRequest
    {
        $schedule = ($this->ensureWikiEditingAllowed)($viewer, $editRequest->page);

        if ($editRequest->status !== WikiEditRequestStatus::AwaitingConfirmation) {
            throw new DomainException('這個修改目前無法再調整。');
        }

        if (count($editRequest->refinements) >= (int) config('wiki.max_refinements')) {
            throw new DomainException('已達再調整的次數上限，請送出或放棄這份草稿。');
        }

        ($this->consumeWikiDraftAllowance)($schedule, $editRequest->ip_hash);

        $editRequest->refinements = [...$editRequest->refinements, trim($data->instruction)];
        $editRequest->status = WikiEditRequestStatus::Drafting;
        $editRequest->ai_verdict = [...($editRequest->ai_verdict ?? []), 'lastRefinementFailed' => false];
        $editRequest->saveOrFail();

        GenerateWikiEditDraft::dispatchAfterResponse($editRequest);

        return $editRequest;
    }
}
