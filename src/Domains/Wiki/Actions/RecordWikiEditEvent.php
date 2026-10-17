<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditEventType;
use App\Models\User;
use App\Models\WikiEditEvent;
use App\Models\WikiEditRequest;
use App\Notifications\WikiEditRequestNotPublished;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Appends a step of an edit request to the audit log and, when the edit did
 * not go through, tells the review webhook. A webhook that is down never
 * breaks the edit flow.
 */
final readonly class RecordWikiEditEvent
{
    public function __invoke(WikiEditEventType $type, WikiEditRequest $editRequest, ?string $detail = null, ?User $user = null): WikiEditEvent
    {
        $editRequest->loadMissing('page');

        $event = new WikiEditEvent;
        $event->event = $type;
        $event->wiki_edit_request_id = $editRequest->id;
        $event->request_uuid = $editRequest->uuid;
        $event->page_slug = $editRequest->page?->slug ?? $editRequest->draft_slug ?? $editRequest->requested_slug;
        $event->page_title = $editRequest->page?->title ?? $editRequest->draft_title ?? $editRequest->requested_title;
        $event->instruction = $editRequest->instruction;
        $event->student_schedule_id = $editRequest->student_schedule_id;
        $event->ip_hash = $editRequest->ip_hash;
        $event->user_id = $user?->id;
        $event->detail = $detail;
        $event->saveOrFail();

        $this->notify($event);

        return $event;
    }

    private function notify(WikiEditEvent $event): void
    {
        $webhook = config('services.discord.webhooks.wiki_review');

        if (! $event->event->isUnsuccessful() || blank($webhook)) {
            return;
        }

        try {
            Notification::route('discord-webhook', $webhook)
                ->notifyNow(new WikiEditRequestNotPublished($event));
        } catch (Throwable $exception) {
            Log::warning('Wiki edit webhook failed.', ['event' => $event->id, 'exception' => $exception->getMessage()]);
        }
    }
}
