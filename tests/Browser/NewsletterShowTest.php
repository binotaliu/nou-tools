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
            ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止', 'description' => '逾期不受理'],
            ['start' => '2026-09-21', 'end' => '2026-09-30', 'name' => '115上學期加退選'],
        ],
    ]);
    NewsletterItem::factory()->for($issue, 'issue')->create(['headline' => '期中考開始報名', 'source_name' => '教務處']);
    NewsletterItem::factory()->for($issue, 'issue')->arts('學務處')->create(['headline' => '校園攝影展徵件']);
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
        ->assertSee('前言')
        ->assertSee('這兩週要注意期中考報名。')
        ->assertSee('本期行事曆')
        ->assertSeeIn('[data-testid="newsletter-highlights"]', '逾期不受理')
        ->assertSee('9/25（五）')
        ->assertPresent('[data-testid="calendar-day-2026-09-25"]')
        ->assertSeeIn('[data-testid="calendar-event-1-0"]', '期中考報名截止')
        // The 9/21–9/30 event is one bar per week, not repeated in every day.
        ->assertSeeIn('[data-testid="calendar-event-2-0"]', '115上學期加退選')
        ->assertSeeIn('[data-testid="calendar-event-2-1"]', '115上學期加退選')
        ->assertSee('空大新消息')
        ->assertSee('期中考開始報名')
        ->assertSee('藝文活動')
        ->assertSee('校園攝影展徵件')
        ->assertSee('各中心消息')
        ->assertSee('讀書會招募')
        ->assertSee('臺北中心')
        ->assertSee('秋季健行活動報名')
        ->assertSee('臺中中心')
        ->assertSee('浣熊站長的自言自語');
});

it('draws weekends in red and keeps every calendar week the same height', function () {
    $page = visit('/newsletter/2026-W39')->assertPresent('[data-testid="calendar-week-1"]');

    $heights = json_decode($page->script(
        "JSON.stringify([...document.querySelectorAll('[data-testid^=\"calendar-week-\"]')].map(week => Math.round(week.getBoundingClientRect().height)))"
    ), true);

    expect($heights)->toHaveCount(2)
        ->and(count(array_unique($heights)))->toBe(1);

    $colors = json_decode($page->script(
        "JSON.stringify([0, 5, 6].map(i => getComputedStyle(document.querySelector('[data-testid=\"calendar-week-0\"]').children[7 + i]).color))"
    ), true);

    expect($colors[0])->not->toBe($colors[1])
        ->and($colors[1])->toBe($colors[2]);
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
