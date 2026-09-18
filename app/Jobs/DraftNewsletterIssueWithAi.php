<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NewsletterIssue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use NouTools\Domains\Newsletter\Actions\DraftNewsletterWithAi;
use Throwable;

/**
 * Wraps DraftNewsletterWithAi for the admin panel, which dispatches it after
 * the response so a slow model call doesn't hit the request timeout. The
 * app runs no queue worker, so it is not meant to be pushed onto a queue.
 */
final class DraftNewsletterIssueWithAi implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NewsletterIssue $issue,
    ) {}

    public function handle(DraftNewsletterWithAi $draftNewsletterWithAi): void
    {
        try {
            $draftNewsletterWithAi($this->issue);
        } catch (Throwable $exception) {
            Log::error('Newsletter AI draft failed.', [
                'issue_key' => $this->issue->issue_key,
                'exception' => $exception->getMessage(),
            ]);
            report($exception);
        }
    }
}
