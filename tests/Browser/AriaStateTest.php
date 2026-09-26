<?php

use App\Models\Course;

// WCAG 4.1.2 (name, role, value) and 1.4.1: a control whose state is only
// shown by colour must also expose it to assistive tech. Every check reads the
// live DOM after clicking, so a stale attribute would fail too.

it('marks the selected 學習指導中心 with aria-pressed', function () {
    $page = visit(route('directory.index'))->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertAttribute('[data-testid="center-button-0"]', 'aria-pressed', 'false');

    $page->click('[data-testid="center-button-0"]')
        ->assertAttribute('[data-testid="center-button-0"]', 'aria-pressed', 'true');

    $page->click('[data-testid="center-button-1"]')
        ->assertAttribute('[data-testid="center-button-0"]', 'aria-pressed', 'false')
        ->assertAttribute('[data-testid="center-button-1"]', 'aria-pressed', 'true');
});

it('exposes the study room view switch and seat filter as pressed toggles', function () {
    $page = visit('/study-room')->resize(1280, 900);
    $page->assertNoJavaScriptErrors()
        ->assertAttribute('[data-testid="study-room-view-map"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-testid="study-room-view-list"]', 'aria-pressed', 'false');

    $page->click('[data-testid="study-room-view-list"]')
        ->assertAttribute('[data-testid="study-room-view-map"]', 'aria-pressed', 'false')
        ->assertAttribute('[data-testid="study-room-view-list"]', 'aria-pressed', 'true');

    $unpressed = $page->script("[...document.querySelectorAll('[data-testid=\"study-room-seat-list-filters\"] button')].filter(b => !b.hasAttribute('aria-pressed')).length");
    expect($unpressed)->toBe(0);
});

it('exposes settings tabs and switches with aria-selected and aria-checked', function () {
    $page = visit('/settings')->resize(1280, 900);
    $page->assertNoJavaScriptErrors();

    $state = json_decode($page->script(<<<'JS'
JSON.stringify((() => {
  const bad = [];
  for (const list of document.querySelectorAll('[role=tablist]')) {
    const tabs = [...list.querySelectorAll('[role=tab]')].filter(t => t.getClientRects().length);
    if (!tabs.length) continue;
    const selected = tabs.filter(t => t.getAttribute('aria-selected') === 'true').length;
    if (tabs.some(t => !['true', 'false'].includes(t.getAttribute('aria-selected'))) || selected !== 1) bad.push('tablist ' + (list.getAttribute('aria-label') || ''));
  }
  for (const s of document.querySelectorAll('[role=switch]')) {
    if (s.getClientRects().length && !['true', 'false'].includes(s.getAttribute('aria-checked'))) bad.push('switch ' + (s.getAttribute('aria-label') || s.textContent.trim().slice(0, 20)));
  }
  return bad;
})())
JS), true);

    expect($state)->toBe([]);

    $page->click('[data-testid="settings-appearance"] [data-testid="font-size-large"]')
        ->assertAttribute('[data-testid="settings-appearance"] [data-testid="font-size-large"]', 'aria-checked', 'true');
});

it('exposes the course schedule filter dropdowns with aria-expanded', function () {
    Course::factory()->create(['term' => config('app.current_semester')]);

    $page = visit(route('course.schedule'))->resize(1280, 900);
    $page->assertNoJavaScriptErrors()
        ->assertAttribute('button[aria-label^="學系"]', 'aria-expanded', 'false');

    $page->click('button[aria-label^="學系"]')
        ->assertAttribute('button[aria-label^="學系"]', 'aria-expanded', 'true');
});

it('gives every visible icon-only control an accessible name', function (string $url) {
    $page = visit($url)->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    $unnamed = json_decode($page->script(<<<'JS'
JSON.stringify([...document.querySelectorAll('button, a[href], [role=button], [role=tab], [role=switch]')].filter(e => {
  if (!e.getClientRects().length || e.closest('[aria-hidden="true"], .leaflet-container')) return false;
  const byId = (e.getAttribute('aria-labelledby') || '').split(/\s+/).map(i => document.getElementById(i)?.textContent.trim() || '').join('');
  const text = (e.textContent || '').replace(/\s+/g, '');
  return !(text || byId || (e.getAttribute('aria-label') || '').trim() || (e.getAttribute('title') || '').trim() || e.querySelector('img[alt]:not([alt=""])'));
}).map(e => e.tagName.toLowerCase() + (e.dataset.testid ? '[' + e.dataset.testid + ']' : '') + '.' + String(e.className).slice(0, 40)))
JS), true);

    expect($unnamed)->toBe([]);
})->with([
    'home' => '/',
    'schedule create' => '/schedules/create',
    'settings' => '/settings',
    'about' => '/about',
    'video classes' => '/video-classes',
    'study room preview' => '/study-room',
    'discount stores' => '/discount-stores',
    'announcements' => '/announcements',
    'directory' => '/directory',
]);
