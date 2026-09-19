<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NewsletterIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Newsletter\Actions\PublishDueNewsletterIssues;
use NouTools\Domains\Newsletter\Schedule\NewsletterCadence;

final class PublishDueNewsletterIssuesCommand extends Command
{
    protected $signature = 'newsletter:publish-due';

    protected $description = '發布已標記為「待發布」且到了發刊日的雙週報';

    public function handle(PublishDueNewsletterIssues $publishDueNewsletterIssues): int
    {
        $result = $publishDueNewsletterIssues(Date::now(NewsletterCadence::TIMEZONE));

        $result['published']->each(fn (NewsletterIssue $issue) => $this->info("已發布 {$issue->issue_key}。"));
        $result['unfinished']->each(fn (NewsletterIssue $issue) => $this->warn("{$issue->issue_key} 已到發刊日但尚未標記為待發布，未發布。"));

        if ($result['published']->isEmpty() && $result['unfinished']->isEmpty()) {
            $this->info('沒有需要發布的雙週報。');
        }

        return self::SUCCESS;
    }
}
