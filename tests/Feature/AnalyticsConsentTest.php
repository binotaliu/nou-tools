<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\putJson;

it('grants analytics by default for Taiwan visitors and hides the banner', function () {
    $response = get(route('about'), ['CF-IPCountry' => 'TW']);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsConsent', ['granted' => true, 'showBanner' => false]));
});

it('denies analytics by default and shows the banner for non-Taiwan visitors', function () {
    $response = get(route('about'), ['CF-IPCountry' => 'US']);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsConsent', ['granted' => false, 'showBanner' => true]));
});

it('denies analytics and shows the banner when CF-IPCountry is missing', function () {
    $response = get(route('about'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsConsent', ['granted' => false, 'showBanner' => true]));
});

it('denies analytics and shows the banner when CF-IPCountry is unrecognized', function () {
    $response = get(route('about'), ['CF-IPCountry' => 'XX']);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsConsent', ['granted' => false, 'showBanner' => true]));
});

it('lets an explicit denied cookie override a Taiwan default', function () {
    $this->withCookie('analytics_consent', 'denied');

    $response = get(route('about'), ['CF-IPCountry' => 'TW']);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsConsent', ['granted' => false, 'showBanner' => false]));
});

it('lets an explicit granted cookie override a non-Taiwan default', function () {
    $this->withCookie('analytics_consent', 'granted');

    $response = get(route('about'), ['CF-IPCountry' => 'US']);

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsConsent', ['granted' => true, 'showBanner' => false]));
});

it('sets the analytics consent cookie via PUT and returns the new state', function () {
    $response = putJson(route('analytics-consent.update'), ['granted' => true]);

    $response->assertOk();
    $response->assertJson(['ok' => true, 'granted' => true]);
    $response->assertCookie('analytics_consent', 'granted');

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === 'analytics_consent');

    expect($cookie)->not->toBeNull();
    expect($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(170)->timestamp);
});

it('requires the granted flag on the consent endpoint', function () {
    $response = putJson(route('analytics-consent.update'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['granted']);
});
