<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('renders the install page', function () {
    get(route('pwa.install'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Pwa/Install'));
});

it('is not linked from the sitemap yet', function () {
    get(route('sitemap'))
        ->assertSuccessful()
        ->assertDontSee(route('pwa.install'), false);
});
