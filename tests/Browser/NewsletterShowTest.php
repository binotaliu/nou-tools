<?php

use App\Models\NewsletterColumn;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;

// Newsletter Markdown (intro, summaries, column bodies) is rendered
// server-side and mounted with v-html, like articles, so any :::checklist or
// :::tabs an editor writes into a column only works if the page runs
// useMarkdownContainers over it. That hydration isn't observable from a
// Feature test.

beforeEach(function (): void {
    config(['newsletter.anchor_date' => '2026-09-21']);

    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create([
        'highlights_intro' => '這兩週要注意期中考報名。',
        'highlights_events' => [
            ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止'],
            ['start' => '2026-09-21', 'end' => '2026-09-30', 'name' => '115上學期加退選'],
        ],
    ]);
    NewsletterItem::factory()->for($issue, 'issue')->create(['headline' => '期中考開始報名', 'source_name' => '教務處']);
    NewsletterItem::factory()->for($issue, 'issue')->centers('臺北中心')->create(['headline' => '讀書會招募']);
    NewsletterItem::factory()->for($issue, 'issue')->centers('臺中中心')->create(['headline' => '秋季健行活動報名']);
    NewsletterColumn::factory()->for($issue, 'issue')->create([
        'title' => '浣熊站長的自言自語',
        'body' => <<<'MD'
            :::checklist
            - [ ] 報名期中考
            - [ ] 繳交作業
            :::

            ::::tabs
            :::tab 甲頁
            甲頁內容
            :::
            :::tab 乙頁
            乙頁內容
            :::
            ::::
            MD,
    ]);
});

it('renders every section of an issue', function () {
    visit('/newsletter/2026-W39')
        ->assertNoJavaScriptErrors()
        ->assertSee('本期重點事項')
        ->assertSee('9/25（五）')
        ->assertSee('期中考報名截止')
        ->assertPresent('[data-testid="calendar-day-2026-09-25"]')
        ->assertSeeIn('[data-testid="calendar-day-2026-09-25"]', '期中考報名截止')
        ->assertSeeIn('[data-testid="calendar-day-2026-09-25"]', '115上學期加退選')
        ->assertSeeIn('[data-testid="calendar-day-2026-09-21"]', '115上學期加退選')
        ->assertSee('空大新消息')
        ->assertSee('期中考開始報名')
        ->assertSee('各中心消息')
        ->assertSee('讀書會招募')
        ->assertSee('臺北中心')
        ->assertSee('秋季健行活動報名')
        ->assertSee('臺中中心')
        ->assertSee('浣熊站長的自言自語');
});

it('shows a cover image only when one is set', function () {
    visit('/newsletter/2026-W39')
        ->assertNotPresent('[data-testid="newsletter-cover-image"]');

    NewsletterIssue::query()->where('issue_key', '2026-W39')->sole()->update(['cover_image' => 'newsletter-covers/cover.jpg']);

    visit('/newsletter/2026-W39')
        ->assertPresent('[data-testid="newsletter-cover-image"]')
        ->assertNotPresent('[data-testid="newsletter-cover-image-credit"]');
});

it('credits the Unsplash photographer when the cover image has one', function () {
    NewsletterIssue::query()->where('issue_key', '2026-W39')->sole()->update([
        'cover_image' => 'newsletter-covers/cover.jpg',
        'cover_image_credit_name' => 'Nathan Dumlao',
        'cover_image_credit_url' => 'https://unsplash.com/@nate_dumlao',
    ]);

    visit('/newsletter/2026-W39')
        ->assertPresent('[data-testid="newsletter-cover-image-credit"]')
        ->assertSeeIn('[data-testid="newsletter-cover-image-credit"]', 'Nathan Dumlao');
});

it('hydrates markdown containers inside columns', function () {
    $page = visit('/newsletter/2026-W39')->wait(1);

    $firstItem = '.md-checklist ul li:nth-child(1)';
    $firstCheckbox = $firstItem.' input[type="checkbox"]';

    $page->assertNoJavaScriptErrors()
        ->assertSee('甲頁內容')
        ->assertDontSee('乙頁內容')
        ->click($firstCheckbox)
        ->assertChecked($firstCheckbox)
        ->assertDataAttribute($firstItem, 'checked', 'true');
});
