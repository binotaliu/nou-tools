<?php

use App\Models\NewsletterIssue;

// WCAG 1.1.1: every image carries an alt attribute (empty when decorative),
// and every inline SVG is either hidden from assistive technology or named.

const IMAGE_AUDIT_SCRIPT = <<<'JS'
JSON.stringify([
    ...[...document.querySelectorAll('img')].filter(i => !i.hasAttribute('alt')).map(i => 'img without alt: ' + i.src),
    ...[...document.querySelectorAll('svg')]
        .filter(s => !s.closest('[aria-hidden="true"]') && s.getAttribute('aria-hidden') !== 'true')
        .filter(s => !(s.getAttribute('role') === 'img' && (s.getAttribute('aria-label') || s.querySelector('title'))))
        .filter(s => !s.closest('button, a, [role="button"]') || !s.closest('button, a, [role="button"]').textContent.trim())
        .map(s => 'unlabelled svg: ' + (s.getAttribute('class') || '').slice(0, 40)),
])
JS;

it('gives every image an alt attribute on key pages', function (string $path) {
    config(['newsletter.anchor_date' => '2026-09-21']);
    NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create(['cover_image' => 'cover.jpg']);

    $page = visit($path);

    expect(json_decode($page->script(IMAGE_AUDIT_SCRIPT), true))->toBe([]);
})->with([
    'home' => '/',
    'newsletter index' => '/newsletter',
    'newsletter issue' => '/newsletter/2026-W39',
    'study room preview' => '/study-room',
    'alt-uu' => '/alt-uu',
]);
