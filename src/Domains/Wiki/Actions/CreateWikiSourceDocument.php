<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiSourceDocument;
use DomainException;
use NouTools\Domains\Wiki\DataTransferObjects\FetchWikiSourceDocumentDTO;

/**
 * Registers a source document and queues its first fetch. A (series, term)
 * holds one document: fetching it again is a re-fetch of that one.
 */
final readonly class CreateWikiSourceDocument
{
    public function __construct(private QueueWikiSourceDocumentFetch $queueWikiSourceDocumentFetch) {}

    /**
     * @throws DomainException when the series already has a document for that term
     */
    public function __invoke(FetchWikiSourceDocumentDTO $data): WikiSourceDocument
    {
        $exists = WikiSourceDocument::query()
            ->where('series', $data->series)
            ->where('term', $data->term)
            ->exists();

        if ($exists) {
            throw new DomainException('這個系列在該學期已經有來源文件，請從那一筆重新擷取。');
        }

        $document = (new WikiSourceDocument)->fillFromDTO($data);
        $document->saveOrFail();

        return ($this->queueWikiSourceDocumentFetch)($document);
    }
}
