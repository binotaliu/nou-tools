<?php

// WCAG 1.4.3 (text contrast), 1.4.11 (non-text contrast) and 1.4.1 (links in
// running text are not distinguished by colour alone), measured from
// getComputedStyle in a real browser in both colour schemes.
//
// Deliberately skipped, all inside the script below:
// - Text or controls over a background image or gradient (no single colour to
//   measure against), elements with opacity < 1 and text-transparent print text.
// - Disabled / aria-disabled controls (inactive components are exempt), the
//   aria-hidden subtrees (decorative), .sr-only text, the Leaflet map.
// - Borders that are not the only boundary: only inputs, selects, textareas
//   and switch tracks are checked, never cards or dividers.

const CONTRAST_AUDIT_SCRIPT = <<<'JS'
JSON.stringify((() => {
  const cv = document.createElement('canvas'); cv.width = cv.height = 1;
  const cx = cv.getContext('2d', { willReadFrequently: true });
  const rgba = c => { cx.clearRect(0, 0, 1, 1); cx.fillStyle = '#000'; cx.fillStyle = c; cx.fillRect(0, 0, 1, 1); const d = cx.getImageData(0, 0, 1, 1).data; return [d[0], d[1], d[2], d[3] / 255]; };
  const over = (f, b) => [0, 1, 2].map(i => f[i] * f[3] + b[i] * (1 - f[3]));
  const lum = c => { const [r, g, b] = c.map(v => { v /= 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
  const ratio = (a, b) => { const x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); };
  const describe = e => `${e.tagName.toLowerCase()}${e.dataset.testid ? '[' + e.dataset.testid + ']' : ''}${e.id ? '#' + e.id : ''}`;
  const exempt = e => e.closest('[aria-hidden="true"], .sr-only, .leaflet-container, [disabled], [aria-disabled="true"], script, style, [hidden]');
  const faded = e => { for (let n = e; n && n !== document.documentElement; n = n.parentElement) { const s = getComputedStyle(n); if (parseFloat(s.opacity) < 1 || s.visibility === 'hidden') return true; } return false; };
  // Effective opaque background behind e (null when an image/gradient is involved).
  const backdrop = e => {
    const layers = [];
    for (let n = e; n; n = n.parentElement) {
      const s = getComputedStyle(n);
      if (s.backgroundImage !== 'none') return null;
      const c = rgba(s.backgroundColor);
      if (c[3] > 0) { layers.push(c); if (c[3] === 1) break; }
    }
    let base = layers.length && layers[layers.length - 1][3] === 1 ? layers.pop().slice(0, 3) : [255, 255, 255];
    while (layers.length) base = over(layers.pop(), base);
    return base;
  };

  const text = [], border = [], links = [];
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  let n;
  while ((n = walker.nextNode())) {
    if (!n.textContent.trim()) continue;
    const p = n.parentElement;
    if (!p.getClientRects().length || exempt(p) || faded(p)) continue;
    const s = getComputedStyle(p);
    if (s.webkitTextFillColor === 'rgba(0, 0, 0, 0)' || rgba(s.color)[3] === 0) continue;
    const bg = backdrop(p);
    if (!bg) continue;
    const fg = over(rgba(s.color), bg);
    const size = parseFloat(s.fontSize), bold = parseInt(s.fontWeight) >= 700;
    const need = size >= 24 || (size >= 18.66 && bold) ? 3 : 4.5;
    const r = ratio(fg, bg);
    if (r < need - 0.02) text.push(`${describe(p)} "${n.textContent.trim().slice(0, 24)}" ${r.toFixed(2)} < ${need}`);
  }

  const sel = 'input:not([type=hidden]):not([type=file]):not([type=range]):not([type=color]), select, textarea, [role=switch]';
  for (const e of document.querySelectorAll(sel)) {
    if (!e.getClientRects().length || exempt(e) || faded(e)) continue;
    const s = getComputedStyle(e);
    const outer = e.parentElement && backdrop(e.parentElement);
    if (!outer) continue;
    if (e.getAttribute('role') === 'switch') {
      const box = e.getBoundingClientRect();
      if (box.width > 60 || rgba(s.backgroundColor)[3] === 0) continue;
      const r = ratio(over(rgba(s.backgroundColor), outer), outer);
      if (r < 2.98) border.push(`${describe(e)} switch track ${r.toFixed(2)} < 3`);
      continue;
    }
    if (['checkbox', 'radio'].includes(e.type) && s.appearance !== 'none') continue;
    if (!parseFloat(s.borderTopWidth) || s.borderTopStyle === 'none') continue;
    const r = ratio(over(rgba(s.borderTopColor), backdrop(e) || outer), outer);
    if (r < 2.98) border.push(`${describe(e)}[${e.type || e.tagName}] border ${r.toFixed(2)} < 3`);
  }

  for (const a of document.querySelectorAll('a[href]')) {
    if (!a.getClientRects().length || exempt(a)) continue;
    const parent = a.closest('p, li, td, dd, dt, blockquote');
    if (!parent || parent.querySelector('nav')) continue;
    const own = [...parent.childNodes].some(c => c.nodeType === 3 && c.textContent.trim().length > 0);
    if (!own) continue;
    const s = getComputedStyle(a);
    if (!s.textDecorationLine.includes('underline') && !(parseFloat(s.borderBottomWidth) > 0 && s.borderBottomStyle !== 'none')) {
      links.push(`${describe(a)} "${a.textContent.trim().slice(0, 24)}"`);
    }
  }
  return { text: [...new Set(text)], border: [...new Set(border)], links: [...new Set(links)] };
})())
JS;

$contrastAudit = function (string $url, bool $dark): array {
    $page = visit($url)->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    if ($dark) {
        // The CSP blocks injected styles, so let colour transitions settle instead
        // of measuring them half-way between the schemes.
        $page->script("document.documentElement.classList.add('dark')");
        $page->script('new Promise(resolve => setTimeout(resolve, 800))');
    }

    $result = json_decode($page->script(CONTRAST_AUDIT_SCRIPT), true);

    return array_map(fn (array $items) => array_map(fn (string $item) => ($dark ? 'dark ' : 'light ')."{$url}: {$item}", $items), $result);
};

$pages = [
    'home' => '/',
    'schedule find' => '/schedules/my',
    'schedule create' => '/schedules/create',
    'settings' => '/settings',
    'about' => '/about',
    'accessibility' => '/accessibility',
    'article list' => '/kb',
    'video classes' => '/video-classes',
    'study room preview' => '/study-room',
    'discount stores' => '/discount-stores',
    'discount store form' => '/discount-stores/create',
    'announcements' => '/announcements',
    'directory' => '/directory',
];

it('keeps text at WCAG AA contrast', function (string $url, bool $dark) use ($contrastAudit) {
    expect($contrastAudit($url, $dark)['text'])->toBe([]);
})->with($pages)->with([false, true]);

it('keeps input borders and switch tracks at 3:1 against their surroundings', function (string $url, bool $dark) use ($contrastAudit) {
    expect($contrastAudit($url, $dark)['border'])->toBe([]);
})->with($pages)->with([false, true]);

it('underlines links inside running text', function (string $url) use ($contrastAudit) {
    expect($contrastAudit($url, false)['links'])->toBe([]);
})->with($pages);
