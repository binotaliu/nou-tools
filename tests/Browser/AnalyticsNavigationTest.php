<?php

use App\Models\Announcement;

// GA's page_view is fired from resources/js/app.js's `router.on('navigate',
// ...)` hook on every Inertia navigation (SPA or initial). GA itself only
// loads in production (see resources/views/app.blade.php), so `window.gtag`
// is undefined here and the hook no-ops — this just proves that hook (and
// reading `page.props.analyticsPage`/`document.title`) never throws when a
// real client-side Inertia transition happens, via the announcements
// pagination Link.

it('does not error when an Inertia client-side navigation happens', function () {
    Announcement::factory()->count(31)->create();

    $page = visit(route('announcements.index'));

    $page->assertNoJavaScriptErrors()
        ->click('a[href*="page=2"]')
        ->assertNoJavaScriptErrors();
});

it('pins the masked page_location on gtag instead of the real URL', function () {
    Announcement::factory()->count(31)->create();

    $page = visit(route('announcements.index'));

    $page->script('window.__gtagCalls = []; window.gtag = function () { window.__gtagCalls.push(Array.from(arguments)) }');

    $page->click('a[href*="page=2"]')
        ->waitForEvent('load')
        ->assertNoJavaScriptErrors();

    $calls = $page->script('JSON.stringify(window.__gtagCalls)');
    $calls = json_decode($calls, true);

    $pageView = collect($calls)->first(fn ($call) => ($call[0] ?? null) === 'event' && ($call[1] ?? null) === 'page_view');

    expect($pageView)->not->toBeNull()
        ->and($pageView[2]['page_location'])->toEndWith('/announcements')
        ->and($pageView[2]['page_location'])->not->toContain('page=2');
});
