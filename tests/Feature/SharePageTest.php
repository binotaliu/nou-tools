<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('renders the share page with the home url', function () {
    get(route('share'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('url', route('home')));
});

it('lists the share page in the sitemap', function () {
    get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee(route('share'), false);
});
