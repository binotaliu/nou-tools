<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('renders the accessibility help page', function () {
    get(route('accessibility'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Accessibility/Show'));
});

it('lists the accessibility help page in the sitemap', function () {
    get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee(route('accessibility'), false);
});
