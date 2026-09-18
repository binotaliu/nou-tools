<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;

final readonly class FindViewableNewsletterIssue
{
    /**
     * Unpublished issues are only returned when $includeUnpublished is set,
     * which lets admins preview a draft at its public URL.
     */
    public function __invoke(string $issueKey, bool $includeUnpublished = false): ?NewsletterIssue
    {
        return NewsletterIssue::query()
            ->where('issue_key', $issueKey)
            ->when(! $includeUnpublished, fn ($query) => $query->published())
            ->with(['items', 'columns'])
            ->first();
    }
}
