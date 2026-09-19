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
        $latestIssue = NewsletterIssue::query()
            ->published()
            ->orderByDesc('publishes_on')
            ->first();

        $issues = NewsletterIssue::query()
            ->published()
            ->when($latestIssue !== null, fn ($query) => $query->whereKeyNot($latestIssue->getKey()))
            ->orderByDesc('publishes_on')
            ->paginate(20)
            ->through(fn (NewsletterIssue $issue): NewsletterIssueSummaryViewModel => NewsletterIssueSummaryViewModel::fromModel($issue));

        return new NewsletterIndexPageData(
            title: (string) config('newsletter.title'),
            latestIssue: $latestIssue !== null ? NewsletterIssueSummaryViewModel::fromModel($latestIssue) : null,
            issues: $issues,
            feedUrl: route('newsletter.feed'),
        );
    }
}
