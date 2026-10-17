<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WikiEditRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NouTools\Domains\Wiki\Actions\DraftAndSubmitWikiEdit;

/**
 * Wraps DraftAndSubmitWikiEdit for the public edit flow, which dispatches it
 * after the response so the visitor gets the status page at once and it
 * polls until the draft is written and submitted. The app runs no queue
 * worker, so it is not meant to be pushed onto a queue. DraftWikiEdit
 * records its own failures on the request.
 */
final class GenerateWikiEditDraft implements ShouldQueue
{
    use Queueable;

    // Only applies on a queue worker (the semester rollover queues drafts
    // there): writing a page with source reads takes minutes, and DraftWikiEdit
    // records its own failures, so there is nothing to retry.
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public WikiEditRequest $editRequest,
    ) {}

    public function handle(DraftAndSubmitWikiEdit $draftAndSubmitWikiEdit): void
    {
        // Runs after the response inside the web worker, where PHP's
        // execution limit still applies; source fetches can take minutes.
        set_time_limit(0);

        $draftAndSubmitWikiEdit($this->editRequest);
    }
}
