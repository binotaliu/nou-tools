<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\NewsletterSection;
use App\Models\Announcement;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NouTools\Domains\Newsletter\Ai\NewsletterHighlightsWriter;
use NouTools\Domains\Newsletter\Ai\NewsletterItemCurator;

final readonly class DraftNewsletterWithAi
{
    private const array WEEKDAYS = ['日', '一', '二', '三', '四', '五', '六'];

    public function __construct(
        private ListNewsletterCandidateAnnouncements $listNewsletterCandidateAnnouncements,
    ) {}

    /**
     * Let the AI editor pick and summarise announcements for every news
     * section and write the highlights intro, then replace the issue's
     * items and intro with the result. Every AI call happens before
     * anything is written, so a failure leaves the issue untouched.
     *
     * 藝文活動 is curated first and its picks are withheld from 空大新消息,
     * so no announcement is printed twice.
     *
     * Ids the model returns that weren't in its candidate list are dropped.
     *
     * @throws DomainException when the issue is already published
     */
    public function __invoke(NewsletterIssue $issue): NewsletterIssue
    {
        if ($issue->isPublished()) {
            throw new DomainException("雙週報 {$issue->issue_key} 已發布，不能重新產生草稿。");
        }

        $candidatesBySection = ($this->listNewsletterCandidateAnnouncements)($issue->covers_from, $issue->covers_to);

        /** @var Collection<string, Collection<int, array{announcement: Announcement, headline: string, summary: string, read_source: bool}>> $curatedBySection */
        $curatedBySection = collect();
        $claimedIds = [];

        foreach ([NewsletterSection::Arts, NewsletterSection::News, NewsletterSection::Centers] as $section) {
            $curated = $this->curate(
                $section,
                $candidatesBySection[$section->value]->reject(fn (Announcement $announcement): bool => in_array($announcement->id, $claimedIds, true)),
            );

            $claimedIds = [...$claimedIds, ...$curated->map(fn (array $item): int => $item['announcement']->id)->all()];
            $curatedBySection[$section->value] = $curated;
        }

        $intro = $this->writeIntro($issue, $curatedBySection);

        DB::transaction(function () use ($issue, $curatedBySection, $intro): void {
            $issue->items()->delete();

            foreach ($curatedBySection as $section => $curatedItems) {
                foreach ($curatedItems->values() as $position => $curated) {
                    $item = new NewsletterItem;
                    $item->fill([
                        'newsletter_issue_id' => $issue->id,
                        'announcement_id' => $curated['announcement']->id,
                        'section' => $section,
                        'source_name' => $curated['announcement']->source_name,
                        'url' => $curated['announcement']->url,
                        'headline' => $curated['headline'],
                        'summary' => $curated['summary'],
                        'position' => $position,
                    ]);
                    $item->saveOrFail();
                }
            }

            $issue->highlights_intro = $intro;
            $issue->ai_drafted_at = Date::now();
            $issue->saveOrFail();
        });

        return $issue->refresh();
    }

    /**
     * @param  Collection<int, Announcement>  $candidates
     * @return Collection<int, array{announcement: Announcement, headline: string, summary: string, read_source: bool}>
     */
    private function curate(NewsletterSection $section, Collection $candidates): Collection
    {
        if ($candidates->isEmpty()) {
            return collect();
        }

        $candidates = $candidates
            ->take((int) config('newsletter.ai.max_candidates_per_section'))
            ->keyBy('id');

        // Plain-text blocks with the URL alone on its line: when URLs sat
        // inside JSON, the model sliced them badly (trailing `","date":…`)
        // and wasted its web_fetch budget on URLs that don't exist.
        $prompt = "以下是本期「{$section->label()}」的候選公告，每則以空行分隔：\n\n"
            .$candidates->map(fn (Announcement $announcement): string => implode("\n", array_filter([
                "id: {$announcement->id}",
                "來源: {$announcement->source_name}／{$announcement->category}",
                '日期: '.($announcement->published_at ?? $announcement->fetched_at)?->toDateString(),
                "標題: {$announcement->title}",
                filled($announcement->tags) ? '標籤: '.implode('、', $announcement->tags) : null,
                "url: {$announcement->url}",
            ])))->implode("\n\n");

        $response = (new NewsletterItemCurator($section))->prompt($prompt);

        $curated = collect($response['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item)
                && $candidates->has($item['announcement_id'] ?? null)
                && filled($item['headline'] ?? null))
            ->unique('announcement_id')
            ->take((int) config('newsletter.ai.max_items_per_section'))
            ->map(fn (array $item): array => [
                'announcement' => $candidates->get($item['announcement_id']),
                'headline' => Str::limit(trim((string) $item['headline']), 250, ''),
                'summary' => trim((string) ($item['summary'] ?? '')),
                'read_source' => (bool) ($item['read_source'] ?? false),
            ])
            ->values();

        Log::info('Newsletter section curated.', [
            'section' => $section->value,
            'candidates' => $candidates->count(),
            'selected' => $curated->count(),
            'read_source' => $curated->where('read_source', true)->count(),
        ]);

        return $curated;
    }

    /**
     * @param  Collection<string, Collection<int, array{announcement: Announcement, headline: string, summary: string, read_source: bool}>>  $curatedBySection
     */
    private function writeIntro(NewsletterIssue $issue, Collection $curatedBySection): string
    {
        $events = collect($issue->highlights_events ?? [])
            ->map(fn (array $event): string => ($event['start'] === $event['end']
                ? "- {$this->formatDate($event['start'])}：{$event['name']}"
                : "- {$this->formatDate($event['start'])} 至 {$this->formatDate($event['end'])}：{$event['name']}")
                .(filled($event['description'] ?? null) ? "（{$event['description']}）" : ''))
            ->implode("\n");

        $headlines = $curatedBySection
            ->flatMap(fn (Collection $items): Collection => $items)
            ->map(fn (array $item): string => "- {$item['headline']}：{$item['summary']}")
            ->implode("\n");

        $prompt = implode("\n\n", [
            "本期為 {$issue->issue_key}，於 {$this->formatDate($issue->publishes_on->toDateString())} 發刊，涵蓋 {$this->formatDate($issue->highlights_from->toDateString())} 至 {$this->formatDate($issue->highlights_to->toDateString())}。",
            "這兩週的校曆事件：\n".($events !== '' ? $events : '（無）'),
            "本期收錄的消息：\n".($headlines !== '' ? $headlines : '（無）'),
        ]);

        $response = (new NewsletterHighlightsWriter)->prompt($prompt);

        return trim((string) ($response['intro'] ?? ''));
    }

    private function formatDate(string $date): string
    {
        $parsed = Date::parse($date);

        return "{$parsed->month} 月 {$parsed->day} 日（".self::WEEKDAYS[$parsed->dayOfWeek].'）';
    }
}
