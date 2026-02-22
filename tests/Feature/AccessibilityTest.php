<?php

use function Pest\Laravel\get;

it('renders skip-to-main link on pages', function () {
    get('/')
        ->assertOk()
        ->assertSee('href="#main-content"', false)
        ->assertSee('跳到主要區塊');
});
