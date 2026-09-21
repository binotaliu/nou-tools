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
