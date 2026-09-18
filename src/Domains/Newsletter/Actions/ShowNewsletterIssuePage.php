<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use NouTools\Domains\Newsletter\PageData\NewsletterIssuePageData;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueSummaryViewModel;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueViewModel;

final readonly class ShowNewsletterIssuePage
{
    public function __construct(
        private FindViewableNewsletterIssue $findViewableNewsletterIssue,
        private RenderNewsletterMarkdown $renderNewsletterMarkdown,
    ) {}

    public function __invoke(string $issueKey, bool $includeUnpublished = false): ?NewsletterIssuePageData
    {
        $issue = ($this->findViewableNewsletterIssue)($issueKey, $includeUnpublished);

        if ($issue === null) {
            return null;
        }

        $previousIssue = NewsletterIssue::query()
            ->published()
            ->where('publishes_on', '<', $issue->publishes_on)
            ->orderByDesc('publishes_on')
            ->first();

        $nextIssue = NewsletterIssue::query()
            ->published()
            ->where('publishes_on', '>', $issue->publishes_on)
            ->orderBy('publishes_on')
            ->first();

        return new NewsletterIssuePageData(
            newsletterTitle: (string) config('newsletter.title'),
            issue: NewsletterIssueViewModel::fromModel($issue, $this->renderNewsletterMarkdown),
            previousIssue: $previousIssue !== null ? NewsletterIssueSummaryViewModel::fromModel($previousIssue) : null,
            nextIssue: $nextIssue !== null ? NewsletterIssueSummaryViewModel::fromModel($nextIssue) : null,
            feedUrl: route('newsletter.feed'),
        );
    }
}
