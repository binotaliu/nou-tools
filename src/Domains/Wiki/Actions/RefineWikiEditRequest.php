<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Jobs\GenerateWikiEditDraft;
use App\Models\WikiEditRequest;
use DomainException;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\DataTransferObjects\RefineWikiEditRequestDTO;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

/**
 * 再調整: the visitor read the draft and asks for one more change to it.
 * It works before and after the draft was submitted: a request waiting for
 * review is redrafted and resubmitted, a published one gets a further
 * revision on top of what went live (see WikiEditRequest::canBeRefined()).
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

        $editRequest = DB::transaction(function () use ($editRequest, $data, $schedule): WikiEditRequest {
            // Locked so an admin approving the same request can't race it.
            $editRequest = WikiEditRequest::query()->lockForUpdate()->findOrFail($editRequest->id);

            if (count($editRequest->refinements) >= (int) config('wiki.max_refinements')) {
                throw new DomainException('已達再調整的次數上限。');
            }

            if (! $editRequest->canBeRefined()) {
                throw new DomainException('這個修改目前無法再調整。');
            }

            ($this->consumeWikiDraftAllowance)($schedule, $editRequest->ip_hash);

            $editRequest->refinements = [...$editRequest->refinements, trim($data->instruction)];
            $editRequest->ai_verdict = [
                ...($editRequest->ai_verdict ?? []),
                'lastRefinementFailed' => false,
                // Where a refinement the editor can't carry out leaves the request.
                'statusBeforeRefinement' => $editRequest->status->value,
            ];
            $editRequest->status = WikiEditRequestStatus::Drafting;
            $editRequest->saveOrFail();

            return $editRequest;
        });

        GenerateWikiEditDraft::dispatchAfterResponse($editRequest);

        return $editRequest;
    }
}
