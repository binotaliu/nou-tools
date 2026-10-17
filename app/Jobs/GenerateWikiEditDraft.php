<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WikiEditEventType;
use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NouTools\Domains\Wiki\Actions\DraftAndSubmitWikiEdit;
use NouTools\Domains\Wiki\Actions\RecordWikiEditEvent;
use Throwable;

/**
 * Wraps DraftAndSubmitWikiEdit for every edit request: the visitor flow and
 * the semester rollover both queue it for the worker, so the visitor gets
 * the status page at once and it polls until the draft is written and
 * submitted. DraftWikiEdit records its own failures on the request; failed()
 * covers the ones it can't (a timeout, a killed worker).
 */
final class GenerateWikiEditDraft implements ShouldQueue
{
    use Queueable;

    // Writing a page with source reads takes minutes, and DraftWikiEdit
    // records its own failures, so there is nothing to retry.
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public WikiEditRequest $editRequest,
    ) {}

    public function handle(DraftAndSubmitWikiEdit $draftAndSubmitWikiEdit): void
    {
        $draftAndSubmitWikiEdit($this->editRequest);
    }

    public function failed(Throwable $exception): void
    {
        $editRequest = WikiEditRequest::query()->find($this->editRequest->id);

        if ($editRequest === null || $editRequest->status !== WikiEditRequestStatus::Drafting) {
            return;
        }

        $editRequest->ai_verdict = [...($editRequest->ai_verdict ?? []), 'problems' => ['queue job failed']];
        $editRequest->status = WikiEditRequestStatus::Failed;
        $editRequest->saveOrFail();

        app(RecordWikiEditEvent::class)(WikiEditEventType::Failed, $editRequest, 'queue job failed: '.$exception->getMessage());
    }
}
