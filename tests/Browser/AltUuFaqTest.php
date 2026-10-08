<?php

it('lists the Alt UU FAQ collapsed and expands an answer on click', function () {
    $page = visit(route('alt-uu'))->assertSee('常見問題');

    $page->assertVisible('[data-testid="alt-uu-faq-0"] summary')
        ->assertDontSee('與國立空中大學官方沒有任何隸屬或合作關係')
        ->click('[data-testid="alt-uu-faq-0"] summary')
        ->assertSee('與國立空中大學官方沒有任何隸屬或合作關係');
});

it('renders line breaks in FAQ answers', function () {
    $page = visit(route('alt-uu'))->assertSee('常見問題');

    $page->click('[data-testid="alt-uu-faq-2"] summary');

    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"alt-uu-faq-2\"] p')).whiteSpace"))
        ->toBe('pre-line');
});

it('aligns the hero copy to the left of the phone illustrations', function () {
    $page = visit(route('alt-uu'))->assertVisible('[data-testid="alt-uu-hero-illustration"]');

    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"alt-uu-hero-copy\"]')).textAlign"))
        ->toBe('left');

    expect($page->script("document.querySelector('[data-testid=\"alt-uu-hero-copy\"]').getBoundingClientRect().right <= document.querySelector('[data-testid=\"alt-uu-hero-illustration\"]').getBoundingClientRect().left"))
        ->toBeTrue();
});

it('brings a side phone to the front when it is clicked', function () {
    $page = visit(route('alt-uu'))->assertVisible('[data-testid="alt-uu-hero-illustration"]');

    $position = fn (string $key) => $page->script("document.querySelector('[data-testid=\"alt-uu-hero-phone-{$key}\"]').dataset.position");

    expect($position('courses'))->toBe('front');

    $page->click('[data-testid="alt-uu-hero-phone-materials"]');

    expect($position('materials'))->toBe('front')
        ->and($position('player'))->toBe('right')
        ->and($position('courses'))->toBe('left');
});

it('hides the site header and footer with ?embed', function () {
    visit(route('alt-uu', ['embed' => 1]))
        ->assertSee('常見問題')
        ->assertScript("getComputedStyle(document.querySelector('[data-testid=\"site-header\"]')).display", 'none')
        ->assertScript("getComputedStyle(document.querySelector('[data-testid=\"site-footer\"]')).display", 'none');
});

it('keeps the site header and footer without ?embed', function () {
    visit(route('alt-uu'))
        ->assertSee('常見問題')
        ->assertVisible('[data-testid="site-header"]')
        ->assertVisible('[data-testid="site-footer"]');
});
