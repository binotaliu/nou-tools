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
        '1' => '#main-content',
        '2' => '/schedules/my',
        '3' => 'theme-switcher-toggle',
    ]);

    $count = $page->script("document.querySelectorAll('[accesskey]').length");
    expect($count)->toBe(4);
});

it('names the header navigation and lets the skip link land on main', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('nav[aria-label="主要導覽"][data-testid="header-nav"]')
        ->assertPresent('main#main-content[tabindex="-1"]');
});

it('renders a single main landmark on article pages', function () {
    $page = visit('/kb/about-nou');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="article-share-button"]');

    expect($page->script("document.querySelectorAll('main').length"))->toBe(1);
});

it('puts accesskey 8 on the search field of the course schedule page', function () {
    $page = visit('/courses/schedule');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('input#search[accesskey="8"]');
});

it('puts accesskey 8 on the search field of the discount stores page', function () {
    $page = visit('/discount-stores');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('input#search[accesskey="8"]');
});

it('puts accesskey 8 on the course search field of the schedule editor', function () {
    $page = visit('/schedules/create');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('input#course-search[accesskey="8"]');
});

it('keeps accesskey 2 usable when the header nav is collapsed', function () {
    $page = visit('/')->resize(390, 844);

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="footer-accessibility-link"]')
        ->assertPresent('a[data-testid="accesskey-my-schedule"][accesskey="2"][tabindex="-1"]');

    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"header-nav\"]')).display"))->toBe('none');
});

it('documents the study room keys and keyboard use on the accessibility page', function () {
    $page = visit('/accessibility');

    $page->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="accessibility-study-room-keys"]', '快速入座')
        ->assertSeeIn('[data-testid="accessibility-study-room-keys"]', '朗讀目前的計時狀態')
        ->assertSeeIn('[data-testid="accessibility-study-room"]', '方向鍵')
        ->assertDontSeeIn('[data-testid="accessibility-limitations"]', '座位按鈕的名稱較長');

    $rows = $page->script("document.querySelectorAll('[data-testid=\"accessibility-study-room-keys\"] tbody tr').length");
    expect($rows)->toBe(5);
});
