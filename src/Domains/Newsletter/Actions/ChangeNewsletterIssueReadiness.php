<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\NewsletterIssueStatus;
use App\Models\NewsletterIssue;
use DomainException;

final readonly class ChangeNewsletterIssueReadiness
{
    /**
     * Move an unpublished issue between 草稿 and 待發布. Only 待發布 issues are
     * picked up by the Monday auto-publish run.
     *
     * @throws DomainException when the issue is already published
     */
    public function __invoke(NewsletterIssue $issue, bool $isReady): NewsletterIssue
    {
        if ($issue->isPublished()) {
            throw new DomainException("雙週報 {$issue->issue_key} 已發布。");
        }

        $issue->status = $isReady ? NewsletterIssueStatus::Ready : NewsletterIssueStatus::Draft;
        $issue->saveOrFail();

        return $issue;
    }
}
