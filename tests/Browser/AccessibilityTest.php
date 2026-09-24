<?php

// The skip-to-main link lives in AppLayout.vue (resources/js/Layouts/AppLayout.vue),
// so it's only present in the client-rendered DOM after Vue hydrates, not in
// the server-rendered HTML a Feature test sees.

it('renders skip-to-main link on pages', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('a[href="#main-content"]')
        ->assertSee('跳到主要區塊');
});

it('offers the accessibility help page from the skip links and the footer', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="skip-link-accessibility"][href="/accessibility"]')
        ->assertPresent('[data-testid="footer-accessibility-link"][href="/accessibility"]');
});

it('assigns each global accesskey to one element', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="footer-accessibility-link"]');

    $keys = json_decode($page->script(
        "JSON.stringify(Object.fromEntries([...document.querySelectorAll('[accesskey]')].map(e => [e.accessKey, e.getAttribute('href') ?? e.dataset.testid])))"
    ), true);

    expect($keys)->toBe([
        '0' => '/accessibility',
        '1' => '/',
        '2' => '#main-content',
        '3' => '/schedules/my',
        '9' => 'theme-switcher-toggle',
    ]);

    $count = $page->script("document.querySelectorAll('[accesskey]').length");
    expect($count)->toBe(5);
});

it('names the header navigation and lets the skip link land on main', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('nav[aria-label="主要導覽"][data-testid="header-nav"]')
        ->assertPresent('main#main-content[tabindex="-1"]');
});
