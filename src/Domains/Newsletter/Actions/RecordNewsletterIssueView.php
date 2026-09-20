<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use Illuminate\Contracts\Session\Session;

/**
 * Counts an issue once per session: with no login, the session is the
 * closest thing to a distinct reader. Previews of unpublished issues never
 * count.
 */
final readonly class RecordNewsletterIssueView
{
    private const string SESSION_KEY = 'newsletter.viewed_issue_ids';

    public function __invoke(NewsletterIssue $issue, Session $session): void
    {
        if (! $issue->isPublished()) {
            return;
        }

        if (in_array($issue->id, $session->get(self::SESSION_KEY, []), true)) {
            return;
        }

        $session->push(self::SESSION_KEY, $issue->id);

        $issue->increment('view_count');
    }
}
