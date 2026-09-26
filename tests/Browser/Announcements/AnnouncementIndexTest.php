<?php

use App\Models\Announcement;

beforeEach(function () {
    config()->set('announcements.sources', [
        ['name' => '教務處', 'category' => '考試資訊', 'is_active' => true],
        ['name' => '學務處', 'category' => '活動資訊', 'is_active' => true],
    ]);

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
        ->assertSeeIn('#announcement-filter-panel', '學務處');

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
