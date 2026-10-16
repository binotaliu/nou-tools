<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Notifications\WikiEditRequestNeedsReview;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The visitor confirmed the draft: publish it when the rules allow,
 * otherwise queue it for an admin. If the page changed since the draft was
 * written, the request becomes a conflict instead and the visitor can have
 * it redrafted against the new version.
 */
final readonly class SubmitWikiEditRequest
{
    public function __construct(
        private ResolveWikiPublishDecision $resolveWikiPublishDecision,
        private PublishWikiEditRequest $publishWikiEditRequest,
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
                ? $page->current_revision_id !== $editRequest->base_revision_id
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

        if ($editRequest->status === WikiEditRequestStatus::PendingReview && filled(config('services.discord.webhooks.wiki_review'))) {
            Notification::route('discord-webhook', config('services.discord.webhooks.wiki_review'))
                ->notifyNow(new WikiEditRequestNeedsReview($editRequest));
        }

        return $editRequest;
    }
}
