<?php

// The reader-chosen text size lives in localStorage and on `html[data-font-size]`;
// Tailwind sizes in rem, so the root font-size is what actually moves the UI.

it('scales the root font size from the settings page and remembers it across a reload', function () {
    $page = visit('/settings');

    $page->assertVisible('[data-testid="settings-appearance"]')
        ->click('[data-testid="settings-appearance"] [data-testid="font-size-xlarge"]');

    expect($page->script('document.documentElement.dataset.fontSize'))->toBe('xlarge');
    expect($page->script("localStorage.getItem('font-size')"))->toBe('xlarge');
    expect($page->script('getComputedStyle(document.documentElement).fontSize'))->toBe('20px');

    $page->navigate('/settings')->assertVisible('[data-testid="settings-appearance"]');

    expect($page->script('document.documentElement.dataset.fontSize'))->toBe('xlarge');
    expect($page->script('getComputedStyle(document.documentElement).fontSize'))->toBe('20px');
    expect($page->script(
        "document.querySelector('[data-testid=\"settings-appearance\"] [data-testid=\"font-size-xlarge\"]').getAttribute('aria-checked')"
    ))->toBe('true');
});

it('offers the font size choice in the header popover too', function () {
    $page = visit('/')->resize(1280, 800);

    $page->assertSee('自習室')
        ->click('header [data-testid="theme-switcher-toggle"] >> visible=true')
        ->assertPresent('header [data-testid="font-size-large"] >> visible=true')
        ->click('header [data-testid="font-size-large"] >> visible=true');

    expect($page->script('getComputedStyle(document.documentElement).fontSize'))->toBe('18px');
});

it('falls back to the default size for an unknown stored value', function () {
    $page = visit('/settings');

    $page->script("localStorage.setItem('font-size', 'huge')");
    $page->navigate('/settings')->assertVisible('[data-testid="settings-appearance"]');

    expect($page->script(
        "document.querySelector('[data-testid=\"font-size-default\"]').getAttribute('aria-checked')"
    ))->toBe('true');
});
