<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\PageData;

use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueSummaryViewModel;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueViewModel;
use Spatie\LaravelData\Resource;

final class NewsletterIssuePageData extends Resource
{
    public function __construct(
        public string $newsletterTitle,
        public NewsletterIssueViewModel $issue,
        public ?NewsletterIssueSummaryViewModel $previousIssue,
        public ?NewsletterIssueSummaryViewModel $nextIssue,
        public string $feedUrl,
    ) {}
}
