<?php

// The center picker (list + map) is driven entirely by Alpine.js state
// (selectedKey, selectedCenter) and Leaflet map rendering, so this behaviour
// is only observable with a real browser rather than the server-rendered
// Feature test.
//
// Centers must be selected via their `[data-testid="center-button-N"]`
// selector (N being their 0-based position among 學習指導中心 entries), not
// by their visible name: the mobile `<select>` renders an `<option>` with
// the same text as the desktop button, and Playwright's text-based click()
// matches both non-strictly, grabs the hidden `<option>` first, and then
// hangs waiting for it to become visible.

it('shows a placeholder until a 學習指導中心 is selected, then reveals its map and details', function () {
    $page = visit(route('directory.index'));

    $page->assertNoJavaScriptErrors()
        ->assertVisible('[data-testid="center-placeholder"]')
        ->assertMissing('[data-testid="center-details"]')
        ->click('[data-testid="center-button-0"]')
        ->assertMissing('[data-testid="center-placeholder"]')
        ->assertVisible('[data-testid="center-details"]')
        ->assertVisible('[data-testid="center-map"]')
        ->assertSee('202 基隆市中正區北寧路2號（海洋大學海空大樓8樓）')
        ->assertSee('02-2462-9938')
        ->assertSee('開啟中心網站')
        ->assertSee('交通資訊')
        ->assertNoJavaScriptErrors();
});

it('updates the details and map when switching between 學習指導中心 entries', function () {
    $page = visit(route('directory.index'));

    $page->click('[data-testid="center-button-0"]')
        ->assertSee('02-2462-9938')
        ->click('[data-testid="center-button-1"]')
        ->assertSee('02-2282-9355 分機 3111')
        ->assertSee('02-2282-9355 分機 3112')
        ->assertDontSee('02-2462-9938')
        ->assertNoJavaScriptErrors();
});

it('does not show a 交通資訊 button for centers without transport info', function () {
    $page = visit(route('directory.index'));

    $page->click('[data-testid="center-button-14"]')
        ->assertVisible('[data-testid="center-details"]')
        ->assertSee('新北市蘆洲區中正路172號（與台北中心共用）')
        ->assertMissing('[data-testid="center-transport-button"]')
        ->assertNoJavaScriptErrors();
});

it('hides the leaflet map embed and shows an offline notice when offline', function () {
    $page = visit(route('directory.index'));

    $page->click('[data-testid="center-button-0"]')
        ->assertVisible('[data-testid="center-map"]');

    $page->script("Alpine.store('network').offline = true");

    $page->assertSee('目前處於離線狀態')
        ->assertMissing('[data-testid="center-map"]')
        ->assertVisible('[data-testid="center-map-offline-notice"]')
        ->assertVisible('[data-testid="center-details"]')
        ->assertNoJavaScriptErrors();
});

it('keeps external links (link groups, website, transport, phone) clickable when offline', function () {
    $page = visit(route('directory.index'));

    $page->click('[data-testid="center-button-0"]')
        ->assertVisible('[data-testid="center-website-button"]');

    // Mirrors how app.js actually detects offline (a real 'offline' event),
    // so updateOfflineLinkStates() runs and toggles pointer-events-none on
    // every non-allowed link, the same way it would for a real disconnect.
    $page->script("window.dispatchEvent(new Event('offline'))");

    $page->assertSee('目前處於離線狀態');

    // Scoped to the page's main content (not the site nav/footer, which are
    // correctly disabled offline) — these are the links this page needs
    // clickable even when the app itself can't reach the network.
    $disabledLinkCount = $page->script(
        "document.querySelectorAll('main a[href].pointer-events-none').length"
    );

    expect($disabledLinkCount)->toBe(0);
});

it('opens the map selection modal when clicking a 學習指導中心 address', function () {
    $page = visit(route('directory.index'));

    $page->click('[data-testid="center-button-0"]')
        ->assertSee('202 基隆市中正區北寧路2號（海洋大學海空大樓8樓）')
        ->click('[data-testid="center-address-button"]')
        ->assertSee('選擇地圖 App')
        ->assertSee('在 OpenStreetMap 開啟')
        ->assertSee('在 Apple 地圖開啟')
        ->assertSee('在 Google 地圖開啟')
        ->assertNoJavaScriptErrors();
});
