<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('renders the settings page', function () {
    get(route('settings'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Settings/Show'));
});

it('server-renders its head tags and keeps the page out of search results', function () {
    get(route('settings'))
        ->assertSuccessful()
        ->assertSee('<title>設定 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="robots" content="noindex, nofollow" />', false);
});

it('is not listed in the sitemap', function () {
    get(route('sitemap'))
        ->assertSuccessful()
        ->assertDontSee(route('settings'), false);
});
