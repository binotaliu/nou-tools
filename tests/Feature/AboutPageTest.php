<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('renders the about page', function () {
    get(route('about'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('About/Show'));
});

it('lists the about page in the sitemap', function () {
    get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee(route('about'), false);
});
