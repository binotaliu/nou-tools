<?php

// The install page's device detection reads the browser, and a headless
// Chromium tab is neither an iPhone nor an installable Android browser, so
// the states are staged by hand: `beforeinstallprompt` is dispatched the way
// Chrome would (app.js captures it), and display-mode isn't standalone.

it('walks an iPhone visitor through the install steps and lets them pick their Safari layout', function () {
    $page = visit(route('pwa.install'))->resize(390, 844);

    $page->assertSee('安裝 NOU 小幫手')
        ->click('[data-testid="pwa-install-tab-ios"]')
        ->assertVisible('[data-testid="pwa-install-guide-ios27"]')
        ->assertMissing('[data-testid="pwa-install-guide-compact"]')
        ->assertMissing('[data-testid="pwa-install-button"]')
        ->assertSee('選擇「加入主畫面」')
        ->click('[data-testid="pwa-install-layout-compact"]')
        ->assertVisible('[data-testid="pwa-install-guide-compact"]')
        ->assertMissing('[data-testid="pwa-install-guide-ios27"]')
        ->click('[data-testid="pwa-install-layout-toolbar"]')
        ->assertVisible('[data-testid="pwa-install-guide-toolbar"]')
        ->assertMissing('[data-testid="pwa-install-guide-compact"]')
        ->click('[data-testid="pwa-install-layout-ipad"]')
        ->assertVisible('[data-testid="pwa-install-guide-ipad"]');
});

it('offers a direct install button once the browser makes the app installable', function () {
    $page = visit(route('pwa.install'))->assertSee('安裝 NOU 小幫手');

    $page->script(<<<'JS'
        (() => {
            const event = new Event('beforeinstallprompt', { cancelable: true })
            event.prompt = () => { window.__promptCalled = true }
            event.userChoice = Promise.resolve({ outcome: 'accepted' })
            window.dispatchEvent(event)
        })()
    JS);

    $page->assertVisible('[data-testid="pwa-install-button"]')
        ->assertMissing('[data-testid="pwa-install-guide-ios27"]')
        ->click('[data-testid="pwa-install-button"]')
        ->assertVisible('[data-testid="pwa-install-done"]')
        ->assertMissing('[data-testid="pwa-install-button"]');

    expect($page->script('window.__promptCalled'))->toBeTrue();
});

it('falls back to manual steps on Android when no install prompt is available', function () {
    visit(route('pwa.install'))
        ->assertSee('安裝 NOU 小幫手')
        ->click('[data-testid="pwa-install-tab-android"]')
        ->assertVisible('[data-testid="pwa-install-android-manual"]')
        ->assertMissing('[data-testid="pwa-install-button"]');
});

it('shows Mac visitors both the Chrome install and the Safari Dock steps', function () {
    visit(route('pwa.install'))
        ->assertSee('安裝 NOU 小幫手')
        ->click('[data-testid="pwa-install-tab-mac"]')
        ->assertVisible('[data-testid="pwa-install-mac"]')
        ->assertVisible('[data-testid="pwa-install-mac-chrome-manual"]')
        ->assertVisible('[data-testid="pwa-install-mac-safari"]')
        ->assertSee('新增到 Dock⋯')
        ->assertMissing('[data-testid="pwa-install-button"]');
});
