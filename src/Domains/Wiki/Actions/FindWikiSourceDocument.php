<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;

/**
 * The ready document of a series for a term, or the series' current one when
 * no term is given (which is also how an evergreen document is found).
 */
final readonly class FindWikiSourceDocument
{
    public function __invoke(string $series, ?string $term = null): ?WikiSourceDocument
    {
        $query = WikiSourceDocument::query()->where('series', trim($series));

        if (filled($term)) {
            return $query
                ->where('status', WikiSourceDocumentStatus::Ready)
                ->where('term', trim((string) $term))
                ->first();
        }

        return $query->current()->orderByDesc('term')->first();
    }
}
