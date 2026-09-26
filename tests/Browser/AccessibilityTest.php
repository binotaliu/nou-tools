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
        "JSON.stringify(Object.fromEntries([...document.querySelectorAll('[accesskey]')].filter(e => e.getClientRects().length || e.classList.contains('sr-only') || e.classList.contains('skip-link')).map(e => [e.accessKey, e.getAttribute('href') ?? e.dataset.testid])))"
    ), true);

    expect($keys)->toBe([
        '0' => '/accessibility',
        '1' => '#main-content',
        '2' => '/schedules/my',
        '3' => 'theme-switcher-toggle',
    ]);

    // The adaptable nav's own copy of key 3 is display:none outside a PWA.
    $count = $page->script("[...document.querySelectorAll('[accesskey]')].filter(e => e.getClientRects().length || e.classList.contains('sr-only') || e.classList.contains('skip-link')).length");
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

it('draws a visible focus indicator on keyboard-focused controls', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="footer-accessibility-link"]');

    $indicator = json_decode($page->script(<<<'JS'
        (() => {
            const el = document.querySelector('[data-testid="footer-accessibility-link"]');
            el.focus();
            const s = getComputedStyle(el);
            return JSON.stringify({
                focused: document.activeElement === el,
                visible: el.matches(':focus-visible'),
                outlineWidth: parseFloat(s.outlineWidth),
                outlineStyle: s.outlineStyle,
                boxShadow: s.boxShadow,
            });
        })()
    JS), true);

    expect($indicator['focused'])->toBeTrue()
        ->and($indicator['visible'])->toBeTrue()
        ->and($indicator['outlineStyle'])->toBe('solid')
        ->and($indicator['outlineWidth'])->toBeGreaterThanOrEqual(2);
});

it('ships forced-colors rules and mentions keyboard focus on the help page', function () {
    // Pest's browser cannot emulate forced-colors, so assert the rules exist.
    $page = visit('/accessibility');

    $page->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="accessibility-focus"]', 'Windows 高對比模式（強制色彩）');

    $hasRules = $page->script(<<<'JS'
        [...document.styleSheets].some((sheet) => {
            try {
                return [...sheet.cssRules].some((rule) => rule.media && rule.media.mediaText.includes('forced-colors') && /highlight/i.test(rule.cssText));
            } catch (e) {
                return false;
            }
        })
    JS);

    expect($hasRules)->toBeTrue();
});

it('announces the new title and focuses main after a client-side page change', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="footer-accessibility-link"]');

    // The initial load announces nothing.
    expect($page->script("document.querySelector('[data-testid=\"route-announcer\"]').textContent"))->toBe('');
    expect($page->script("document.querySelector('[data-testid=\"route-announcer\"]').getAttribute('aria-live')"))->toBe('polite');

    $page->click('[data-testid="footer-accessibility-link"]')
        ->assertPathIs('/accessibility')
        ->wait(0.6);

    expect($page->script("document.querySelector('[data-testid=\"route-announcer\"]').textContent"))->toBe('無障礙說明 - NOU 小幫手');
    expect($page->script('document.activeElement.id'))->toBe('main-content');
});

it('shows errors as an assertive alert that stays and success-style toasts as polite status', function () {
    $page = visit('/schedules/my');

    $page->assertNoJavaScriptErrors()
        ->click('[data-testid="find-schedule-existing"]')
        ->assertPresent('[data-testid="find-schedule-url"]')
        ->fill('[data-testid="find-schedule-url"]', 'not a link')
        ->press('[data-testid="find-schedule-form"] button[type="submit"]')
        ->assertPresent('[data-testid="notification"][role="alert"]');

    // Not dismissed on its own within the old 4 second window.
    $page->wait(5);
    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"notification\"] > div > div')).display"))->not->toBe('none');
});
