<?php

// From `md` up the installed PWA drops the web header for AdaptableNav: a top
// tab bar, or a sidebar the reader switches to (`html[data-nav-style]`, saved
// in localStorage). `html[data-pwa]` is what the head script sets for a
// standalone display mode; a headless tab isn't standalone, so it's set by hand.

const WIDE_TABLET = [820, 1180];
const WIDE_DESKTOP = [1280, 800];
const NARROW_PHONE = [390, 844];

$enterPwaMode = function ($page): void {
    $page->script("document.documentElement.dataset.pwa = ''");
};

// The browser plugin has no assertNotVisible(); a hidden element has no box.
$isShown = fn ($page, string $selector): bool => (bool) $page->script(
    "(() => { const e = document.querySelector('{$selector}'); return !!e && e.getClientRects().length > 0 })()"
);

$currentHref = fn ($page, string $listTestId) => $page->script(
    "document.querySelector('[data-testid=\"{$listTestId}\"] [aria-current=\"page\"]')?.getAttribute('href')"
);

it('shows a brandless top tab bar at the top of the page, without header or footer, in a tablet or desktop PWA', function (array $viewport) use ($enterPwaMode, $isShown) {
    $page = visit('/announcements')->resize(...$viewport);

    $enterPwaMode($page);

    $page->assertVisible('[data-testid="adaptable-nav-tabs"]')->assertMissing('[data-testid="bottom-nav"]');

    expect($isShown($page, '[data-testid="adaptable-nav-sidebar"]'))->toBeFalse()
        ->and($isShown($page, '[data-testid="site-header"]'))->toBeFalse()
        ->and($isShown($page, '[data-testid="site-footer"]'))->toBeFalse()
        ->and($page->script("document.querySelector('[data-testid=\"adaptable-nav-tabs\"]').textContent.includes('NOU 小幫手')"))->toBeFalse()
        ->and($page->script("document.querySelector('[data-testid=\"adaptable-nav-tabs\"]').getBoundingClientRect().top"))->toBe(0)
        ->and($page->script("document.querySelector('[data-testid=\"adaptable-nav-tabs\"]').getBoundingClientRect().top < document.querySelector('main').getBoundingClientRect().top"))->toBeTrue();
})->with([
    'tablet' => [WIDE_TABLET],
    'desktop' => [WIDE_DESKTOP],
]);

it('marks the current page in the tab bar', function () use ($enterPwaMode, $currentHref) {
    $page = visit('/newsletter')->resize(...WIDE_DESKTOP);

    $enterPwaMode($page);
    $page->assertVisible('[data-testid="adaptable-nav-tabs-list"]');

    expect($currentHref($page, 'adaptable-nav-tabs-list'))->toBe('/newsletter');
});

it('switches to a sidebar, keeps the choice across a reload and switches back', function () use ($enterPwaMode, $isShown, $currentHref) {
    $page = visit('/newsletter')->resize(...WIDE_DESKTOP);

    $enterPwaMode($page);

    $page->assertVisible('[data-testid="nav-style-to-sidebar"]')
        ->click('[data-testid="nav-style-to-sidebar"]')
        ->assertVisible('[data-testid="adaptable-nav-sidebar"]');

    expect($isShown($page, '[data-testid="adaptable-nav-tabs"]'))->toBeFalse()
        ->and($page->script("document.querySelector('main').getBoundingClientRect().left"))->toBeGreaterThanOrEqual(256)
        ->and($currentHref($page, 'adaptable-nav-sidebar-list'))->toBe('/newsletter');

    // The head script restores it before first paint, but only for a PWA.
    $page->navigate('/newsletter');
    $enterPwaMode($page);
    $page->assertVisible('[data-testid="adaptable-nav-sidebar"]');

    $page->click('[data-testid="nav-style-to-tabs"]')->assertVisible('[data-testid="adaptable-nav-tabs"]');

    expect($isShown($page, '[data-testid="adaptable-nav-sidebar"]'))->toBeFalse();
});

it('changes the nav style from the settings page', function () use ($enterPwaMode) {
    $page = visit('/settings')->resize(...WIDE_DESKTOP);

    $enterPwaMode($page);

    $page->assertVisible('[data-testid="settings-nav-style"]')
        ->click('[data-testid="nav-style-sidebar"]')
        ->assertVisible('[data-testid="adaptable-nav-sidebar"]');
});

it('keeps the browser tab and the phone PWA unchanged', function () use ($enterPwaMode, $isShown) {
    $tab = visit('/announcements')->resize(...WIDE_DESKTOP);
    $tab->assertVisible('[data-testid="site-header"]');
    expect($isShown($tab, '[data-testid="adaptable-nav-tabs"]'))->toBeFalse();

    $phone = visit('/announcements')->resize(...NARROW_PHONE);
    $enterPwaMode($phone);
    $phone->assertVisible('[data-testid="bottom-nav"]');
    expect($isShown($phone, '[data-testid="adaptable-nav-tabs"]'))->toBeFalse();
});

it('offsets the cookie banner from the sidebar', function () use ($enterPwaMode) {
    $page = visit('/announcements')->resize(...WIDE_DESKTOP);

    $enterPwaMode($page);

    $page->click('[data-testid="nav-style-to-sidebar"]')->assertVisible('[data-testid="cookie-consent-banner"]');

    expect($page->script("document.querySelector('[data-testid=\"cookie-consent-banner\"]').getBoundingClientRect().left"))->toBeGreaterThanOrEqual(256);
});
