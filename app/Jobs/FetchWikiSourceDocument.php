<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WikiSourceDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NouTools\Domains\Wiki\Actions\IngestWikiSourceDocument;

/**
 * Wraps IngestWikiSourceDocument for the admin panel. Downloading and
 * extracting a large PDF takes seconds, so it runs on the queue worker, not
 * in the admin's request. The action records failures on the document, so
 * there is nothing to retry.
 */
final class FetchWikiSourceDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public WikiSourceDocument $document,
    ) {}

    public function handle(IngestWikiSourceDocument $ingestWikiSourceDocument): void
    {
        $ingestWikiSourceDocument($this->document);
    }
}
