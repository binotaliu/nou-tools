<?php

use Illuminate\Support\Facades\File;

// Article Markdown is rendered to HTML server-side (see
// src/Domains/Articles/Markdown/) and mounted into the Inertia page with
// v-html, so Vue never compiles it. The `:::tabs`, `:::checklist`, and
// `:::countdown` containers are therefore hydrated by a separate client-side
// pass, and nothing about that pass is observable from a Feature test —
// tests/Feature/ArticleMarkdownSyntaxTest.php only asserts the HTML string.
//
// These assertions are deliberately behavioural (visible text, checked
// state, persisted storage) rather than framework-specific, so the same test
// holds whether the hydration is driven by Alpine or by Vue.

// Articles are flat files, not database rows: ShowArticlePage reads
// resource_path("articles/{type}/{slug}.md") and 404s only when it's
// missing. No published article uses `:::checklist` or `:::countdown`, and
// the one that uses `:::tabs` repeats a tab label across two blocks (which
// would make a text-based click() ambiguous), so this writes its own
// fixture and removes it again afterwards.
const FIXTURE_SLUG = 'browser-markdown-containers-fixture';

function markdownContainerFixturePath(): string
{
    return resource_path('articles/manual/'.FIXTURE_SLUG.'.md');
}

function markdownContainerFixtureUrl(): string
{
    return route('articles.show', ['type' => 'manual', 'slug' => FIXTURE_SLUG]);
}

beforeEach(function (): void {
    // The countdown's day count is anchored to "today" in Asia/Taipei, so
    // the dates here are far enough either side of now to stay stable: the
    // first item is permanently 已結束, the second permanently counting down.
    File::put(markdownContainerFixturePath(), <<<'MD'
---
title: Markdown 容器測試
author: 測試
published_at: 2020-01-01
description: 瀏覽器測試專用的臨時文章。
---

::::::tabs
:::::tab 外層甲
外層甲內容

::::tabs
:::tab 內層甲
內層甲內容
:::
:::tab 內層乙
內層乙內容
:::
::::
:::::
:::::tab 外層乙
外層乙內容
:::::
::::::

:::checklist
- [ ] 待辦甲
- [ ] 待辦乙
:::

:::countdown
**過去測試**: 2020-01-01 ~ 2020-01-02

**未來測試**: 2099-12-30 ~ 2099-12-31
:::
MD);
});

afterEach(function (): void {
    File::delete(markdownContainerFixturePath());
});

it('shows only the active tab panel and keeps nested tabs independent', function () {
    // Without JavaScript every panel is visible (that's the intended no-JS
    // fallback), so the "not visible" assertions below only mean anything
    // once hydration has run.
    $page = visit(markdownContainerFixtureUrl())->wait(1);

    $page->assertNoJavaScriptErrors()
        ->assertSee('外層甲內容')
        ->assertDontSee('外層乙內容')
        ->assertSee('內層甲內容')
        ->assertDontSee('內層乙內容');

    // Switching the inner tabs must not disturb the outer selection.
    $page->click('內層乙')
        ->assertSee('內層乙內容')
        ->assertDontSee('內層甲內容')
        ->assertSee('外層甲內容')
        ->assertDontSee('外層乙內容');

    // ...and switching the outer tabs hides the whole nested block.
    $page->click('外層乙')
        ->assertSee('外層乙內容')
        ->assertDontSee('外層甲內容')
        ->assertDontSee('內層甲內容')
        ->assertDontSee('內層乙內容');

    // Coming back preserves the inner selection made earlier, which is what
    // proves the two tab groups hold separate state rather than sharing one.
    $page->click('外層甲')
        ->assertSee('外層甲內容')
        ->assertSee('內層乙內容')
        ->assertDontSee('內層甲內容');
});

it('persists checklist ticks across a reload', function () {
    $page = visit(markdownContainerFixtureUrl())->wait(1);

    $firstItem = '.md-checklist ul li:nth-child(1)';
    $firstCheckbox = $firstItem.' input[type="checkbox"]';

    $page->assertNoJavaScriptErrors()
        ->assertNotChecked($firstCheckbox)
        ->assertDataAttribute($firstItem, 'checked', 'false');

    $page->click($firstCheckbox)
        ->assertChecked($firstCheckbox)
        ->assertDataAttribute($firstItem, 'checked', 'true');

    // The storage key is a compatibility contract: readers with ticks saved
    // under it must keep them, so it's asserted literally rather than just
    // "something was stored". Keyed by path + the checklist's index among
    // every .md-checklist on the page.
    $stored = $page->script(
        "localStorage.getItem('nou:article-checklist:/manual/".FIXTURE_SLUG.":0:v1')"
    );

    expect($stored)->toBe('[true,false]');

    // Reload via setTimeout so the script() call returns before the
    // execution context is torn down by the navigation.
    $page->script('setTimeout(() => window.location.reload(), 50)');
    $page->wait(2);

    $page->assertChecked($firstCheckbox)
        ->assertDataAttribute($firstItem, 'checked', 'true')
        ->assertNotChecked('.md-checklist ul li:nth-child(2) input[type="checkbox"]');
});

it('renders countdown day counts against Asia/Taipei today', function () {
    $page = visit(markdownContainerFixtureUrl())->wait(1);

    $page->assertNoJavaScriptErrors();

    // Read the day counts out of their own element rather than asserting on
    // page text: the labels share a prefix with the counts they sit next to,
    // so a text search matches both and trips Playwright's strict mode.
    $past = $page->text('.md-countdown-item:nth-child(1) .md-countdown-days');
    $upcoming = $page->text('.md-countdown-item:nth-child(2) .md-countdown-days');

    expect(trim((string) $past))->toBe('已結束');

    // The future item's exact day count changes daily, so assert its shape
    // rather than a value that would rot overnight.
    expect(trim((string) $upcoming))->toMatch('/^倒數 \d+ 天$/u');
});
