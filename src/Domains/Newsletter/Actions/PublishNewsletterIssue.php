<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\NewsletterIssueStatus;
use App\Models\NewsletterIssue;
use DomainException;
use Illuminate\Support\Facades\Date;

final readonly class PublishNewsletterIssue
{
    /**
     * @throws DomainException when the issue has nothing to publish
     */
    public function __invoke(NewsletterIssue $issue): NewsletterIssue
    {
        if ($issue->isPublished()) {
            return $issue;
        }

        if (blank($issue->highlights_intro) && ! $issue->items()->exists() && ! $issue->columns()->exists()) {
            throw new DomainException("雙週報 {$issue->issue_key} 沒有任何內容，無法發布。");
        }

        $issue->status = NewsletterIssueStatus::Published;
        $issue->published_at = Date::now();
        $issue->saveOrFail();

        return $issue;
    }
}
