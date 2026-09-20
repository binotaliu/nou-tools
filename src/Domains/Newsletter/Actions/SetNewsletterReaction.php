<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use App\Models\NewsletterReaction;
use Illuminate\Contracts\Session\Session;
use NouTools\Domains\Newsletter\DataTransferObjects\SetNewsletterReactionData;

/**
 * Sets, changes or withdraws this session's single reaction to an issue.
 * The upsert leans on the (issue, session) unique key, so two quick taps
 * from the same session can't leave two rows behind.
 */
final readonly class SetNewsletterReaction
{
    public function __invoke(NewsletterIssue $issue, Session $session, SetNewsletterReactionData $data): void
    {
        $sessionHash = NewsletterReaction::sessionHash($session);

        if ($data->reaction === null) {
            NewsletterReaction::query()
                ->where('newsletter_issue_id', $issue->id)
                ->where('session_hash', $sessionHash)
                ->delete();

            return;
        }

        NewsletterReaction::query()->upsert(
            [[
                'newsletter_issue_id' => $issue->id,
                'session_hash' => $sessionHash,
                'reaction' => $data->reaction->value,
            ]],
            ['newsletter_issue_id', 'session_hash'],
            ['reaction'],
        );
    }
}
