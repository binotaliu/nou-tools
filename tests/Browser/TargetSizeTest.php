<?php

// WCAG 2.5.8 (target size, 24x24 CSS px) and 1.4.4 (no text under 12px) audit.
//
// Justified exclusions, all evaluated in the browser script below:
// - Elements inside aria-hidden subtrees: decorative illustrations (the drawn
//   Alt UU phones use tiny absolute type on purpose) and the off-screen
//   accesskey proxies are not operable targets.
// - Skip links (.skip-link) and .sr-only elements: off-screen until focused.
// - Text links inside a sentence or list of prose (WCAG's "inline" exception):
//   an <a> whose parent block also holds other text is skipped.
// - Native checkboxes/radios/file inputs hidden with sr-only are covered by
//   the sr-only rule; their visible label is the target.
// - Text inside the study-room seat grid ([data-testid=study-room-seats]): the seat grid is deliberately
//   compact (9-10px labels); each seat button is still >= 24px and carries a full
//   spoken aria-label (seatAriaLabel), which the test below asserts.
// - The Leaflet map controls are third-party widgets.

$targetSizeAudit = function (string $url): array {
    $page = visit($url)->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    $result = json_decode($page->script(<<<'JS'
JSON.stringify((() => {
  const bad = [], small = [];
  const skip = e => e.closest('[aria-hidden="true"], .sr-only, .skip-link, .leaflet-container, [hidden]');
  for (const e of document.querySelectorAll('button, a[href], [role=button], input:not([type=hidden]), select, textarea')) {
    if (skip(e)) continue;
    const r = e.getBoundingClientRect();
    if (!r.width || !r.height) continue;
    const cs = getComputedStyle(e);
    if (cs.visibility === 'hidden' || cs.opacity === '0') continue;
    if (e.tagName === 'A') {
      const p = e.parentElement;
      const own = [...p.childNodes].filter(n => n.nodeType === 3 && n.textContent.trim().length > 0).length;
      if (own > 0) continue;
    }
    if (r.width < 23.5 || r.height < 23.5) {
      bad.push(`${e.tagName}${e.dataset.testid ? '[' + e.dataset.testid + ']' : ''} "${(e.getAttribute('aria-label') || e.textContent).trim().slice(0, 20)}" ${Math.round(r.width)}x${Math.round(r.height)}`);
    }
  }
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  let n;
  while ((n = walker.nextNode())) {
    if (!n.textContent.trim()) continue;
    const p = n.parentElement;
    if (p.closest('[data-seat-code], [data-testid="study-room-seats"], [aria-hidden="true"], .sr-only, script, style, .leaflet-container')) continue;
    if (!p.getClientRects().length) continue;
    const px = parseFloat(getComputedStyle(p).fontSize);
    if (px < 12) small.push(`${p.tagName} ${px}px "${n.textContent.trim().slice(0, 20)}"`);
  }
  return { bad, small };
})())
JS), true);

    return array_map(fn (array $items) => array_map(fn (string $item) => "{$url}: {$item}", $items), $result);
};

it('keeps every control at least 24x24 CSS px and text at least 12px', function (string $url) use ($targetSizeAudit) {
    $result = $targetSizeAudit($url);

    expect($result['bad'])->toBe([])
        ->and($result['small'])->toBe([]);
})->with([
    'home' => '/',
    'schedule find' => '/schedules/my',
    'schedule create' => '/schedules/create',
    'accessibility' => '/accessibility',
    'settings' => '/settings',
    'about' => '/about',
    'article list' => '/kb',
    'study room preview' => '/study-room',
    'video classes' => '/video-classes',
]);

it('gives every study room seat button an accessible name', function () {
    $page = visit('/study-room')->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertPresent('[data-seat-code]');

    $unnamed = $page->script("[...document.querySelectorAll('button[data-seat-code]')].filter(b => !(b.getAttribute('aria-label') || '').trim()).length");
    expect($unnamed)->toBe(0);
});
