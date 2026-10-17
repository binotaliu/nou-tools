<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;

/**
 * The ready document a given one replaces in its series: the latest earlier
 * term, or for an evergreen document the latest earlier version.
 */
final readonly class FindPreviousWikiSourceDocument
{
    public function __invoke(WikiSourceDocument $document): ?WikiSourceDocument
    {
        $query = WikiSourceDocument::query()
            ->where('series', $document->series)
            ->where('status', WikiSourceDocumentStatus::Ready)
            ->whereKeyNot($document->id);

        if ($document->term !== null) {
            return $query->where('term', '<', $document->term)->orderByDesc('term')->first();
        }

        return $query->whereNull('term')->where('id', '<', $document->id)->orderByDesc('id')->first();
    }
}
