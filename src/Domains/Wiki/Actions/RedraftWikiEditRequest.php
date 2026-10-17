<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditEventType;
use App\Enums\WikiEditRequestStatus;
use App\Jobs\GenerateWikiEditDraft;
use App\Models\WikiEditRequest;
use DomainException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

/**
 * 依最新版本重新產生: after a conflict, the original request (and any
 * refinements, which are then folded into it) is drafted again on top of
 * the page as it is now.
 */
final readonly class RedraftWikiEditRequest
{
    public function __construct(
        private EnsureWikiEditingAllowed $ensureWikiEditingAllowed,
        private ConsumeWikiDraftAllowance $consumeWikiDraftAllowance,
        private RecordWikiEditEvent $recordWikiEditEvent,
    ) {}

    /**
     * @throws DomainException
     * @throws WikiEditingNotAllowedException
     * @throws WikiDraftLimitReachedException
     */
    public function __invoke(WikiEditRequest $editRequest, ?StudentScheduleCookie $viewer): WikiEditRequest
    {
        $schedule = ($this->ensureWikiEditingAllowed)($viewer, $editRequest->page);

        if ($editRequest->status !== WikiEditRequestStatus::Conflict) {
            throw new DomainException('這個修改沒有版本衝突。');
        }

        ($this->consumeWikiDraftAllowance)($schedule, $editRequest->ip_hash);

        if ($editRequest->refinements !== []) {
            $editRequest->instruction = implode("\n\n", [$editRequest->instruction, '補充：', ...$editRequest->refinements]);
            $editRequest->refinements = [];
        }

        $editRequest->base_revision_id = $editRequest->page?->current_revision_id;
        $editRequest->draft_slug = null;
        $editRequest->draft_title = null;
        $editRequest->draft_description = null;
        $editRequest->draft_body = null;
        $editRequest->draft_categories = null;
        $editRequest->draft_edit_summary = null;
        $editRequest->ai_verdict = null;
        $editRequest->status = WikiEditRequestStatus::Drafting;
        $editRequest->saveOrFail();

        ($this->recordWikiEditEvent)(WikiEditEventType::Redrafted, $editRequest);

        GenerateWikiEditDraft::dispatch($editRequest);

        return $editRequest;
    }
}
