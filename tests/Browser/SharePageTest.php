<?php

it('sends the site blurb along with the link through the native share sheet', function () {
    $page = visit('/share');

    $page->script('window.__shared = null; Object.defineProperty(navigator, "share", { value: data => { window.__shared = data; return Promise.resolve() }, configurable: true })');

    $page->click('[data-testid="share-button"]')
        ->assertScript('window.__shared.url', url('/'))
        ->assertScript('window.__shared.text.includes("非官方小工具")', true);
});

it('shows the blurb in the copy-link dialog when the browser cannot share', function () {
    $page = visit('/share');

    $page->script('Object.defineProperty(navigator, "share", { value: undefined, configurable: true })');

    $page->click('[data-testid="share-button"]')
        ->assertPresent('[data-testid="share-modal"]')
        ->assertSeeIn('[data-testid="share-text"]', '非官方小工具')
        ->assertValue('[data-testid="share-modal"] input', url('/'));
});
