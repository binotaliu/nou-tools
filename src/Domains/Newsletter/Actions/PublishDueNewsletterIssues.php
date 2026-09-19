<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\NewsletterIssueStatus;
use App\Models\NewsletterIssue;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class PublishDueNewsletterIssues
{
    public function __construct(
        private PublishNewsletterIssue $publishNewsletterIssue,
    ) {}

    /**
     * Publish every 待發布 issue whose publish date has arrived. Issues still
     * in 草稿 are deliberately left alone (an unfinished issue never goes
     * out) and only logged, so they can be published late by hand.
     *
     * @return array{published: Collection<int, NewsletterIssue>, unfinished: Collection<int, NewsletterIssue>}
     */
    public function __invoke(CarbonInterface $today): array
    {
        $dueIssues = NewsletterIssue::query()
            ->whereIn('status', [NewsletterIssueStatus::Ready, NewsletterIssueStatus::Draft])
            ->whereDate('publishes_on', '<=', $today->toDateString())
            ->orderBy('publishes_on')
            ->get();

        [$readyIssues, $unfinishedIssues] = $dueIssues->partition(
            fn (NewsletterIssue $issue): bool => $issue->status === NewsletterIssueStatus::Ready
        );

        $published = collect();

        foreach ($readyIssues as $issue) {
            try {
                $published->push(($this->publishNewsletterIssue)($issue));
            } catch (DomainException $exception) {
                Log::warning('Newsletter issue marked ready but could not be published.', [
                    'issue_key' => $issue->issue_key,
                    'reason' => $exception->getMessage(),
                ]);
                $unfinishedIssues->push($issue);
            }
        }

        foreach ($unfinishedIssues as $issue) {
            if ($issue->status === NewsletterIssueStatus::Draft) {
                Log::warning('Newsletter issue is past its publish date but still a draft.', [
                    'issue_key' => $issue->issue_key,
                ]);
            }
        }

        return [
            'published' => $published->values(),
            'unfinished' => $unfinishedIssues->values(),
        ];
    }
}
