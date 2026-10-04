<?php

// axe-core (WCAG 2.0/2.1/2.2 A and AA) over the public pages in both colour
// schemes. Fails on serious and critical violations; minor/moderate ones are
// left to the dedicated audits (contrast, target size, ARIA state ...).
//
// axe is evaluated through the driver (like scripts/a11y-scan), which the CSP
// does not restrict, unlike an injected <script> tag.
//
// Allowlist: rule id => why it is accepted. Keep it empty-ish; every entry
// needs a justification.
const AXE_ALLOWED_RULES = [
    // 'rule-id' => 'why this is a justified exception',
];

$axeRun = <<<'JS'
window.axe.run(document, {
  runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] },
}).then(r => JSON.stringify({ passes: r.passes.length, violations: r.violations
  .filter(v => ['serious', 'critical'].includes(v.impact))
  .map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.slice(0, 5).map(n => n.target.join(' ')) })) }))
JS;

// One visit per page: axe runs on the light scheme, then again after switching to dark.
// It is also the text-contrast check (its color-contrast rule) for both schemes.
$axeViolations = function (string $url) use ($axeRun): array {
    $page = visit($url)->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    $page->script(file_get_contents(base_path('node_modules/axe-core/axe.min.js')));

    $violations = [];

    foreach (['light', 'dark'] as $scheme) {
        if ($scheme === 'dark') {
            $page->script("document.documentElement.classList.add('dark')");
            $page->script('new Promise(resolve => setTimeout(resolve, 800))');
        }

        $result = json_decode($page->script($axeRun), true);

        // Guard against axe silently running nothing.
        expect($result['passes'])->toBeGreaterThan(10);

        foreach ($result['violations'] as $v) {
            if (! array_key_exists($v['id'], AXE_ALLOWED_RULES)) {
                $violations[] = "{$scheme} {$url}: {$v['id']} ({$v['impact']}) ".implode(' | ', $v['nodes']);
            }
        }
    }

    return $violations;
};

it('has no serious or critical axe violations', function (string $url) use ($axeViolations) {
    expect($axeViolations($url))->toBe([]);
})->with([
    'home' => '/',
    'schedule find' => '/schedules/my',
    'schedule create' => '/schedules/create',
    'settings' => '/settings',
    'about' => '/about',
    'accessibility' => '/accessibility',
    'article list' => '/kb',
    'article' => '/kb/about-nou',
    'video classes' => '/video-classes',
    'study room preview' => '/study-room',
    'discount stores' => '/discount-stores',
    'discount store form' => '/discount-stores/create',
    'announcements' => '/announcements',
    'directory' => '/directory',
    'school calendar' => '/school-calendar',
]);
