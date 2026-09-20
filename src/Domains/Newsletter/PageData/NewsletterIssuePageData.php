<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\PageData;

use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueSummaryViewModel;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueViewModel;
use NouTools\Domains\Newsletter\ViewModels\NewsletterReactionsViewModel;
use Spatie\LaravelData\Resource;

final class NewsletterIssuePageData extends Resource
{
    public function __construct(
        public string $newsletterTitle,
        public NewsletterIssueViewModel $issue,
        public ?NewsletterIssueSummaryViewModel $previousIssue,
        public ?NewsletterIssueSummaryViewModel $nextIssue,
        public string $feedUrl,
        public string $shareUrl,
        // Distinct sessions that opened the issue; see RecordNewsletterIssueView.
        public int $viewCount,
        public NewsletterReactionsViewModel $reactions,
        public string $reactionUrl,
    ) {}
}
