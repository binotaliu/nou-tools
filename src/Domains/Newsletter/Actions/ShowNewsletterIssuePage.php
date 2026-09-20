<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use Illuminate\Contracts\Session\Session;
use NouTools\Domains\Directory\Actions\ListCentersInDirectoryOrder;
use NouTools\Domains\Newsletter\PageData\NewsletterIssuePageData;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueSummaryViewModel;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueViewModel;

final readonly class ShowNewsletterIssuePage
{
    public function __construct(
        private FindViewableNewsletterIssue $findViewableNewsletterIssue,
        private RenderNewsletterMarkdown $renderNewsletterMarkdown,
        private ListCentersInDirectoryOrder $listCentersInDirectoryOrder,
        private RecordNewsletterIssueView $recordNewsletterIssueView,
        private SummarizeNewsletterReactions $summarizeNewsletterReactions,
    ) {}

    public function __invoke(string $issueKey, Session $session, bool $includeUnpublished = false): ?NewsletterIssuePageData
    {
        $issue = ($this->findViewableNewsletterIssue)($issueKey, $includeUnpublished);

        if ($issue === null) {
            return null;
        }

        ($this->recordNewsletterIssueView)($issue, $session);

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
            issue: NewsletterIssueViewModel::fromModel(
                $issue,
                $this->renderNewsletterMarkdown,
                ($this->listCentersInDirectoryOrder)()->pluck('name')->all(),
            ),
            previousIssue: $previousIssue !== null ? NewsletterIssueSummaryViewModel::fromModel($previousIssue) : null,
            nextIssue: $nextIssue !== null ? NewsletterIssueSummaryViewModel::fromModel($nextIssue) : null,
            feedUrl: route('newsletter.feed'),
            shareUrl: route('newsletter.show', $issue->issue_key),
            viewCount: $issue->view_count,
            reactions: ($this->summarizeNewsletterReactions)($issue, $session),
            reactionUrl: route('newsletter.reaction.update', $issue->issue_key),
        );
    }
}
