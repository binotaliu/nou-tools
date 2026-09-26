<?php

// WCAG 1.4.12 (text spacing), 1.4.10 (reflow at 320px) and 1.4.4 (resize text).
// The reader setting is switched on through the app's own controls and stored
// keys, since the CSP blocks injected <style>.

it('switches text spacing from the settings page, applies the WCAG values and remembers it', function () {
    $page = visit('/settings');

    $page->assertVisible('[data-testid="settings-appearance"]')
        ->click('[data-testid="settings-appearance"] [data-testid="text-spacing-wide"]');

    expect($page->script('document.documentElement.dataset.textSpacing'))->toBe('wide');
    expect($page->script("localStorage.getItem('nou:text-spacing:v1')"))->toBe('wide');
    expect($page->script("getComputedStyle(document.querySelector('main p')).wordSpacing"))->not->toBe('0px');

    $page->navigate('/settings')->assertVisible('[data-testid="settings-appearance"]');

    expect($page->script('document.documentElement.dataset.textSpacing'))->toBe('wide');
    expect($page->script(
        "document.querySelector('[data-testid=\"text-spacing-wide\"]').getAttribute('aria-checked')"
    ))->toBe('true');

    $page->click('[data-testid="settings-appearance"] [data-testid="text-spacing-default"]');

    expect($page->script('document.documentElement.dataset.textSpacing'))->toBeNull();
    expect($page->script("localStorage.getItem('nou:text-spacing:v1')"))->toBeNull();
});

// Justified exclusions: elements inside a scrolling wrapper (overflow-x/auto
// tables, the study-room seat grid), sr-only/aria-hidden decoration and
// deliberately clipped media (img, svg, canvas, iframe) are not text clipping.
it('keeps pages readable at 320px with 150% text and wide text spacing', function (string $url) {
    // Outside Taipei the homepage greeting adds the Taiwan clock, its widest state.
    $page = visit($url)->withTimezone('America/New_York')->resize(320, 800);
    $page->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    $page->script(<<<'JS'
localStorage.setItem('nou:text-spacing:v1', 'wide'); localStorage.setItem('font-size', 'xxlarge'); 0
JS);
    $page->navigate($url)->assertPresent('main#main-content');

    expect($page->script('document.documentElement.dataset.textSpacing'))->toBe('wide');
    expect($page->script('document.documentElement.dataset.fontSize'))->toBe('xxlarge');

    $result = json_decode($page->script(<<<'JS'
JSON.stringify((() => {
  const clipped = [];
  const skip = e => e.closest('[aria-hidden="true"], .sr-only, .leaflet-container, [data-testid=study-room-seats], .overflow-x-auto, table');
  for (const e of document.body.querySelectorAll('*')) {
    if (/^(IMG|SVG|CANVAS|IFRAME|VIDEO|SCRIPT|STYLE)$/i.test(e.tagName) || skip(e)) continue;
    const cs = getComputedStyle(e);
    if (cs.display === 'none' || !e.textContent.trim()) continue;
    if (['hidden', 'clip'].includes(cs.overflowY) && e.scrollHeight - e.clientHeight > 4 && e.clientHeight > 0) {
      clipped.push(`${e.tagName}.${String(e.className).slice(0, 60)} ${e.scrollHeight}>${e.clientHeight}`);
    }
  }
  const wide = [...document.body.querySelectorAll('*')].filter(e => !e.closest('.overflow-x-auto, table') && e.getBoundingClientRect().right > innerWidth + 1).slice(0, 6).map(e => `${e.tagName}.${String(e.className).slice(0, 50)} ${Math.round(e.getBoundingClientRect().right)}`);
  return { wide, overflow: document.documentElement.scrollWidth - innerWidth, clipped };
})())
JS), true);

    expect($result['overflow'])->toBeLessThanOrEqual(0, 'horizontal page scroll: '.$url.' '.json_encode($result['wide']));
    expect($result['clipped'])->toBe([]);
})->with([
    'home' => '/',
    'schedule create' => '/schedules/create',
    'video classes' => '/video-classes',
    'announcements' => '/announcements',
    'article' => '/kb/about-nou',
    'settings' => '/settings',
    'study room preview' => '/study-room',
]);
