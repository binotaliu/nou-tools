<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiSourceDocumentStatus;
use App\Jobs\FetchWikiSourceDocument;
use App\Models\WikiSourceDocument;

/**
 * Marks a document pending and queues its fetch (the first one or a
 * re-fetch). Its earlier sections stay readable until the new ones are in.
 */
final readonly class QueueWikiSourceDocumentFetch
{
    public function __invoke(WikiSourceDocument $document): WikiSourceDocument
    {
        $document->status = WikiSourceDocumentStatus::Pending;
        $document->failure_reason = null;
        $document->saveOrFail();

        FetchWikiSourceDocument::dispatch($document);

        return $document;
    }
}
