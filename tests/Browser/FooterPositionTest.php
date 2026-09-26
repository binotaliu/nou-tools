<?php

it('keeps the footer at the bottom edge of the window on a short page', function () {
    $page = visit('/changelog');

    $page->assertPresent('[data-testid="site-footer"]')
        ->assertScript('Math.abs(document.querySelector("[data-testid=site-footer]").getBoundingClientRect().bottom - window.innerHeight) < 2', true);
});
