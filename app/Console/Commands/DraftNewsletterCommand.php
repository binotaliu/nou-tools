<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use NouTools\Domains\Newsletter\Actions\CreateNewsletterDraft;
use NouTools\Domains\Newsletter\Actions\DraftNewsletterWithAi;
use NouTools\Domains\Newsletter\DataTransferObjects\NewsletterIssueScheduleDTO;
use NouTools\Domains\Newsletter\Schedule\NewsletterCadence;
use Throwable;

final class DraftNewsletterCommand extends Command
{
    protected $signature = 'newsletter:draft
        {--issue= : 指定期號（例如 2026-W39），預設為今天開始編輯的那一期}
        {--without-ai : 只建立空白草稿，不呼叫 AI}
        {--redraft : 即使已有內容，也以 AI 重新產生草稿（會取代現有消息）}';

    protected $description = '建立浣熊的空大雙週報草稿，並以 AI 產生初稿';

    public function handle(
        NewsletterCadence $newsletterCadence,
        CreateNewsletterDraft $createNewsletterDraft,
        DraftNewsletterWithAi $draftNewsletterWithAi,
    ): int {
        try {
            $schedule = $this->resolveSchedule($newsletterCadence);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($schedule === null) {
            $this->info('今天不是雙週報的編輯起始日，沒有需要建立的草稿。');

            return self::SUCCESS;
        }

        $issue = $createNewsletterDraft($schedule);

        $this->info($issue->wasRecentlyCreated
            ? "已建立 {$issue->issue_key} 草稿（{$issue->publishes_on->toDateString()} 發刊）。"
            : "{$issue->issue_key} 已存在（{$issue->status->label()}）。");

        if ($this->option('without-ai')) {
            return self::SUCCESS;
        }

        $hasContent = $issue->ai_drafted_at !== null || $issue->items()->exists();

        if ($hasContent && ! $this->option('redraft')) {
            $this->line('已有內容，略過 AI 初稿；如需重新產生請加上 --redraft。');

            return self::SUCCESS;
        }

        try {
            $issue = $draftNewsletterWithAi($issue);
        } catch (Throwable $exception) {
            report($exception);
            $this->error("AI 初稿產生失敗，草稿已保留：{$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("AI 初稿完成：{$issue->items()->count()} 則消息。");

        return self::SUCCESS;
    }

    private function resolveSchedule(NewsletterCadence $newsletterCadence): ?NewsletterIssueScheduleDTO
    {
        $issueKey = $this->option('issue');

        if (filled($issueKey)) {
            return $newsletterCadence->forIssueKey((string) $issueKey);
        }

        return $newsletterCadence->startingEditingOn(Date::now(NewsletterCadence::TIMEZONE));
    }
}
