<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

// WCAG 3.3.1 / 3.3.2 / 4.1.3: invalid fields are flagged, their message is
// wired up with aria-describedby (and announced through role="alert"), and
// focus lands on the first problem. WCAG 1.3.5 / 4.1.2: every visible control
// has an accessible name.

const FIELD_STATE_SCRIPT = <<<'JS'
JSON.stringify((() => {
    const el = document.activeElement;
    const ids = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
    return {
        id: el.id,
        invalid: el.getAttribute('aria-invalid'),
        described: ids.map(id => document.getElementById(id)).filter(Boolean).map(n => ({ role: n.getAttribute('role'), text: n.textContent.trim() })),
    };
})())
JS;

const CONTROL_NAME_AUDIT_SCRIPT = <<<'JS'
JSON.stringify([...document.querySelectorAll('input, textarea, select')].filter(el => {
    if (el.type === 'hidden') { return false; }
    const visible = !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    // sr-only file/radio inputs are still controls; only skip display:none ones.
    return visible || el.classList.contains('sr-only');
}).filter(el => {
    const labelled = (el.labels && [...el.labels].some(l => l.textContent.trim()))
        || el.getAttribute('aria-label')
        || (el.getAttribute('aria-labelledby') && document.getElementById(el.getAttribute('aria-labelledby')));
    return !labelled;
}).map(el => el.tagName.toLowerCase() + '#' + el.id + '[' + (el.name || el.type) + ']'))
JS;

it('flags the bad link on the find-schedule form and moves focus to it', function () {
    $page = visit(route('schedules.my'))
        ->click('[data-testid="find-schedule-existing"]')
        ->fill('[data-testid="find-schedule-url"]', 'https://nou.tools/schedules/AAAAAAAAAAAAAAAAAAAAAA')
        ->click('[data-testid="find-schedule-submit"]');

    waitUntil($page, "document.querySelector('[data-testid=\"find-schedule-error\"]') !== null");
    waitUntil($page, "document.activeElement.id === 'schedule-url'");

    $state = json_decode($page->script(FIELD_STATE_SCRIPT), true);

    expect($state['invalid'])->toBe('true')
        ->and($state['described'])->toHaveCount(1)
        ->and($state['described'][0]['role'])->toBe('alert')
        ->and($state['described'][0]['text'])->toContain('找不到這個課表');
});

it('ties custom link errors to their field and focuses the first invalid one', function () {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Customize errors']);

    $page = visit(route('schedules.customize', $schedule));
    $page->click('[data-testid="customize-add-link"]');

    waitUntil($page, "document.getElementById('custom-link-url-0') !== null");

    $page->fill('#custom-link-title-0', '範例')
        ->fill('#custom-link-url-0', 'https://example.com')
        ->click('[data-testid="customize-submit"]');

    waitUntil($page, "document.activeElement.id === 'custom-link-url-0'");

    $state = json_decode($page->script(FIELD_STATE_SCRIPT), true);

    expect($state['invalid'])->toBe('true')
        ->and($state['described'])->toHaveCount(1)
        ->and($state['described'][0]['role'])->toBe('alert')
        ->and($state['described'][0]['text'])->toContain('網域');
});

it('gives every visible form control an accessible name on key pages', function () {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Names audit']);

    $controls = 0;

    foreach ([
        route('home'),
        route('schedules.my'),
        route('schedules.customize', $schedule),
        route('schedules.subscribe', $schedule),
        route('course.schedule'),
        route('announcements.index'),
        route('directory.index'),
        route('discount-stores.index'),
        route('discount-stores.create'),
        route('study-room.show'),
    ] as $url) {
        $page = visit($url);
        $page->assertNoJavaScriptErrors();
        waitUntil($page, "document.querySelector('main, h1, h2') !== null");

        expect(json_decode($page->script(CONTROL_NAME_AUDIT_SCRIPT), true))->toBe([], $url);

        $controls += (int) $page->script("document.querySelectorAll('input, textarea, select').length");
    }

    expect($controls)->toBeGreaterThan(20);
});
