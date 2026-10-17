<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditEventType;
use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Notifications\WikiEditRequestNeedsReview;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a fresh draft on: publish it when the rules allow, otherwise queue
 * it for an admin. Runs on its own once the editor has drafted
 * (DraftAndSubmitWikiEdit); the visitor's 確認送出 is left only for drafts
 * that were not submitted automatically. If the page changed since the draft
 * was written, the request becomes a conflict instead and the visitor can
 * have it redrafted against the new version.
 */
final readonly class SubmitWikiEditRequest
{
    public function __construct(
        private ResolveWikiPublishDecision $resolveWikiPublishDecision,
        private PublishWikiEditRequest $publishWikiEditRequest,
        private RecordWikiEditEvent $recordWikiEditEvent,
    ) {}

    /**
     * @throws DomainException when there is no draft awaiting confirmation
     */
    public function __invoke(WikiEditRequest $editRequest): WikiEditRequest
    {
        $editRequest = DB::transaction(function () use ($editRequest): WikiEditRequest {
            $editRequest = WikiEditRequest::query()->lockForUpdate()->findOrFail($editRequest->id);

            if ($editRequest->status !== WikiEditRequestStatus::AwaitingConfirmation) {
                throw new DomainException('這個修改目前無法送出。');
            }

            $page = $editRequest->wiki_page_id === null
                ? null
                : WikiPage::query()->lockForUpdate()->findOrFail($editRequest->wiki_page_id);

            $isStale = $page !== null
                ? $page->current_revision_id !== $editRequest->expectedCurrentRevisionId()
                : ! WikiPage::isSlugAvailable((string) $editRequest->draft_slug);

            if ($isStale) {
                $editRequest->status = WikiEditRequestStatus::Conflict;
                $editRequest->saveOrFail();

                return $editRequest;
            }

            $decision = ($this->resolveWikiPublishDecision)($editRequest);

            $editRequest->ai_verdict = [...($editRequest->ai_verdict ?? []), 'reviewReasons' => $decision->reviewReasons];

            if ($decision->publishesAutomatically()) {
                return ($this->publishWikiEditRequest)($editRequest);
            }

            $editRequest->status = WikiEditRequestStatus::PendingReview;
            $editRequest->saveOrFail();

            return $editRequest;
        });

        match ($editRequest->status) {
            WikiEditRequestStatus::Published => ($this->recordWikiEditEvent)(WikiEditEventType::Published, $editRequest, '自動發布'),
            WikiEditRequestStatus::Conflict => ($this->recordWikiEditEvent)(WikiEditEventType::Conflict, $editRequest, '條目在草稿寫成後已被修改'),
            WikiEditRequestStatus::PendingReview => $this->queueForReview($editRequest),
            default => null,
        };

        return $editRequest;
    }

    private function queueForReview(WikiEditRequest $editRequest): void
    {
        ($this->recordWikiEditEvent)(
            WikiEditEventType::QueuedForReview,
            $editRequest,
            implode('、', $editRequest->ai_verdict['reviewReasons'] ?? []),
        );

        if (filled(config('services.discord.webhooks.wiki_review'))) {
            Notification::route('discord-webhook', config('services.discord.webhooks.wiki_review'))
                ->notifyNow(new WikiEditRequestNeedsReview($editRequest));
        }
    }
}
