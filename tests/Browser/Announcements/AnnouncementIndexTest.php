<?php

use App\Models\Announcement;

beforeEach(function () {
    config()->set('announcements.sources', [
        ['name' => '教務處', 'category' => '考試資訊', 'is_active' => true],
        ['name' => '學務處', 'category' => '活動資訊', 'is_active' => true],
        ['name' => '人文學系', 'category' => '最新消息', 'is_active' => true],
    ]);
    config()->set('announcements.source_groups', ['人文學系' => 'department']);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '期中考公告',
        'published_at' => now()->subDay(),
    ]);
});

it('opens the source filter as a bottom sheet on phones and closes it with Escape', function () {
    $page = visit('/announcements')->resize(390, 844);

    $page->assertNoJavaScriptErrors()
        ->assertSee('期中考公告')
        ->assertMissing('#announcement-filter-panel[role="dialog"]')
        ->click('[data-testid="announcement-filter-open"]')
        ->assertVisible('#announcement-filter-panel[role="dialog"]')
        ->assertSeeIn('#announcement-filter-panel', '各處室');

    expect($page->script('document.getElementById("announcement-filter-panel").contains(document.activeElement)'))->toBeTrue();

    $page->keys('#announcement-filter-panel', 'Escape')
        ->assertMissing('#announcement-filter-panel[role="dialog"]');

    expect($page->script('document.documentElement.classList.contains("overflow-hidden")'))->toBeFalse()
        ->and($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
});

it('shows the source filter as a sidebar on desktop', function () {
    $page = visit('/announcements')->resize(1280, 900);

    $page->assertNoJavaScriptErrors()
        ->assertVisible('#announcement-filter-panel');

    expect($page->script('getComputedStyle(document.querySelector(\'[data-testid="announcement-filter-open"]\')).display'))->toBe('none');
});

it('groups sources like the schedule preferences and selects a whole group at once', function () {
    $page = visit('/announcements')->resize(390, 844);

    $page->assertNoJavaScriptErrors()
        ->click('[data-testid="announcement-filter-open"]')
        ->assertSeeIn('[data-testid="announcement-source-group-administrative"]', '各處室')
        ->assertPresent('[data-testid="announcement-source-group-department"] [aria-label="學系（全選）"]')
        ->assertSeeIn('[data-testid="announcement-source-group-department"]', '0 / 1')
        ->check('[aria-label="學系（全選）"]')
        ->assertSeeIn('[data-testid="announcement-source-group-department"]', '1 / 1');

    expect($page->script('document.querySelector(\'input[aria-label="人文學系"]\').checked'))->toBeTrue();
});

it('describes the applied filter above the list, not the unsubmitted checkboxes', function () {
    $page = visit('/announcements?'.http_build_query(['source_categories' => ['教務處' => ['考試資訊']]]))->resize(390, 844);

    $page->assertNoJavaScriptErrors()
        ->assertSee('教務處')
        ->assertSeeIn('[data-testid="announcement-filter-open"]', '1')
        ->click('[data-testid="announcement-filter-open"]')
        ->check('[aria-label="學系（全選）"]')
        ->keys('#announcement-filter-panel', 'Escape')
        ->assertDontSee('學系（全部）')
        ->assertSeeIn('[data-testid="announcement-filter-open"]', '1');
});
