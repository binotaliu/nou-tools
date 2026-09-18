<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use NouTools\Domains\Newsletter\PageData\NewsletterIndexPageData;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueSummaryViewModel;

final readonly class ShowNewsletterIndexPage
{
    public function __invoke(): NewsletterIndexPageData
    {
        $issues = NewsletterIssue::query()
            ->published()
            ->orderByDesc('publishes_on')
            ->paginate(20)
            ->through(fn (NewsletterIssue $issue): NewsletterIssueSummaryViewModel => NewsletterIssueSummaryViewModel::fromModel($issue));

        return new NewsletterIndexPageData(
            title: (string) config('newsletter.title'),
            issues: $issues,
            feedUrl: route('newsletter.feed'),
        );
    }
}
