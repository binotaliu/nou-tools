<?php

use App\Enums\NewsletterIssueStatus;
use App\Enums\NewsletterSection;
use App\Models\Announcement;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Providers\Tools\WebFetch;
use NouTools\Domains\Newsletter\Ai\NewsletterHighlightsWriter;
use NouTools\Domains\Newsletter\Ai\NewsletterItemCurator;

beforeEach(function () {
    config(['newsletter.anchor_date' => '2026-09-21']);
    config(['announcements.source_groups' => ['教務處' => 'administrative', '臺北中心' => 'center']]);
    config(['school-schedules' => [
        '2026A' => [
            ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止', 'countdown' => false],
            ['start' => '2026-10-10', 'end' => '2026-10-10', 'name' => '國慶日放假', 'countdown' => false],
        ],
    ]]);
});

it('does nothing on a Monday that does not start an editing week', function () {
    $this->travelTo(Date::parse('2026-10-05 09:00', 'Asia/Taipei'));

    $this->artisan('newsletter:draft', ['--without-ai' => true])->assertSuccessful();

    expect(NewsletterIssue::count())->toBe(0);
});

it('creates the upcoming issue at the start of its editing week, idempotently', function () {
    $this->travelTo(Date::parse('2026-09-28 09:00', 'Asia/Taipei'));

    $this->artisan('newsletter:draft', ['--without-ai' => true])->assertSuccessful();
    $this->artisan('newsletter:draft', ['--without-ai' => true])->assertSuccessful();

    $issue = NewsletterIssue::sole();

    expect($issue->issue_key)->toBe('2026-W41')
        ->and($issue->publishes_on->toDateString())->toBe('2026-10-05')
        ->and($issue->covers_from->toDateString())->toBe('2026-09-21')
        ->and($issue->covers_to->toDateString())->toBe('2026-10-04')
        ->and($issue->highlights_from->toDateString())->toBe('2026-10-05')
        ->and($issue->highlights_to->toDateString())->toBe('2026-10-18')
        ->and(array_column($issue->highlights_events, 'name'))->toBe(['國慶日放假']);
});

it('creates a specific issue by key and rejects off-cadence keys', function () {
    $this->artisan('newsletter:draft', ['--issue' => '2026-W39', '--without-ai' => true])->assertSuccessful();
    $this->artisan('newsletter:draft', ['--issue' => '2026-W40', '--without-ai' => true])->assertFailed();

    expect(NewsletterIssue::pluck('issue_key')->all())->toBe(['2026-W39']);
});

it('drafts items and the intro with AI, dropping ids outside the candidate set', function () {
    $news = Announcement::factory()->create(['source_name' => '教務處', 'title' => '115上學期期中考報名', 'published_at' => '2026-09-10 00:00:00']);
    $arts = Announcement::factory()->create(['source_name' => '學務處', 'title' => '校園攝影展徵件', 'published_at' => '2026-09-11 00:00:00']);
    $center = Announcement::factory()->create(['source_name' => '臺北中心', 'title' => '讀書會招募', 'published_at' => '2026-09-12 00:00:00']);
    $outsideWindow = Announcement::factory()->create(['source_name' => '教務處', 'published_at' => '2026-08-01 00:00:00']);

    NewsletterItemCurator::fake(fn (string $prompt): array => match (true) {
        str_contains($prompt, '「藝文活動」') => ['items' => [
            ['announcement_id' => $arts->id, 'headline' => '校園攝影展開始徵件', 'summary' => '歡迎投稿。'],
        ]],
        str_contains($prompt, '「空大新消息」') => ['items' => [
            ['announcement_id' => $news->id, 'headline' => '期中考開始報名', 'summary' => '記得報名。', 'read_source' => true],
            ['announcement_id' => $arts->id, 'headline' => '已被藝文活動收錄', 'summary' => ''],
            ['announcement_id' => $outsideWindow->id, 'headline' => '不該出現', 'summary' => ''],
            ['announcement_id' => 999999, 'headline' => '編造的', 'summary' => ''],
            ['announcement_id' => $news->id, 'headline' => '重複', 'summary' => ''],
        ]],
        default => ['items' => [
            ['announcement_id' => $center->id, 'headline' => '臺北中心讀書會招募中', 'summary' => '歡迎參加。'],
        ]],
    });
    NewsletterHighlightsWriter::fake([['intro' => '這兩週要注意 **9 月 25 日**。']]);

    $this->artisan('newsletter:draft', ['--issue' => '2026-W39'])->assertSuccessful();

    $issue = NewsletterIssue::sole();

    expect($issue->highlights_intro)->toBe('這兩週要注意 **9 月 25 日**。')
        ->and($issue->ai_drafted_at)->not->toBeNull()
        ->and($issue->status)->toBe(NewsletterIssueStatus::Draft)
        ->and($issue->newsItems->map->only(['announcement_id', 'headline', 'source_name', 'url', 'position'])->all())->toBe([
            ['announcement_id' => $news->id, 'headline' => '期中考開始報名', 'source_name' => '教務處', 'url' => $news->url, 'position' => 0],
        ])
        ->and($issue->artItems->map->only(['announcement_id', 'headline', 'source_name'])->all())->toBe([
            ['announcement_id' => $arts->id, 'headline' => '校園攝影展開始徵件', 'source_name' => '學務處'],
        ])
        ->and($issue->centerItems->pluck('headline')->all())->toBe(['臺北中心讀書會招募中']);

    NewsletterItemCurator::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '「空大新消息」')
        && ! str_contains($prompt->prompt, '校園攝影展徵件'));
    NewsletterItemCurator::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '115上學期期中考報名')
        && str_contains($prompt->prompt, "url: {$news->url}\n") || str_ends_with($prompt->prompt, "url: {$news->url}"));
    NewsletterHighlightsWriter::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '期中考報名截止')
        && str_contains($prompt->prompt, '期中考開始報名'));
});

it('skips AI for an issue that already has content unless redrafting', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    NewsletterItem::factory()->for($issue, 'issue')->create(['headline' => '手寫的']);

    NewsletterItemCurator::fake()->preventStrayPrompts();
    NewsletterHighlightsWriter::fake()->preventStrayPrompts();

    $this->artisan('newsletter:draft', ['--issue' => '2026-W39'])->assertSuccessful();

    NewsletterItemCurator::assertNeverPrompted();
    expect($issue->items()->pluck('headline')->all())->toBe(['手寫的']);
});

it('keeps the draft untouched and fails when the AI call fails', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create(['highlights_intro' => '原本的']);
    NewsletterItem::factory()->for($issue, 'issue')->create(['headline' => '手寫的']);
    Announcement::factory()->create(['source_name' => '教務處', 'published_at' => '2026-09-10 00:00:00']);

    NewsletterItemCurator::fake(fn () => throw new RuntimeException('overloaded'));

    $this->artisan('newsletter:draft', ['--issue' => '2026-W39', '--redraft' => true])->assertFailed();

    expect($issue->fresh()->highlights_intro)->toBe('原本的')
        ->and($issue->items()->pluck('headline')->all())->toBe(['手寫的']);
});

it('refuses to redraft a published issue', function () {
    NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();

    $this->artisan('newsletter:draft', ['--issue' => '2026-W39', '--redraft' => true])->assertFailed();
});

it('publishes due ready issues from the scheduler command', function () {
    $ready = NewsletterIssue::factory()->publishingOn('2026-09-21')->ready()->create();

    $this->travelTo(Date::parse('2026-09-21 08:00', 'Asia/Taipei'));

    $this->artisan('newsletter:publish-due')->assertSuccessful();

    expect($ready->fresh()->status)->toBe(NewsletterIssueStatus::Published);
});

it('lets the curator read announcement pages within a capped, school-only budget', function () {
    config(['newsletter.ai.max_fetches_per_section' => 5, 'newsletter.ai.fetch_domains' => ['nou.edu.tw']]);

    $tools = collect((new NewsletterItemCurator(NewsletterSection::News))->tools());

    expect($tools)->toHaveCount(1)
        ->and($tools->first())->toBeInstanceOf(WebFetch::class)
        ->and($tools->first()->maxSearches)->toBe(5)
        ->and($tools->first()->allowedDomains)->toBe(['nou.edu.tw']);
});

it('drops the fetch tool when the fetch budget is zero', function () {
    config(['newsletter.ai.max_fetches_per_section' => 0]);

    $curator = new NewsletterItemCurator(NewsletterSection::Centers);

    expect(collect($curator->tools()))->toBeEmpty()
        ->and((string) $curator->instructions())->toContain('本次不讀取原文');
});
