<?php

use Illuminate\Support\Facades\Route;

// A single- or double-segment path would be swallowed by the catch-all
// `/{type}` / `/{type}/{slug}` article routes, so test routes use three
// segments.

it('renders the Blade error page for HTML requests', function (int $status, string $text) {
    Route::get("/__test/errors/{$status}", fn () => abort($status));

    $this->get("/__test/errors/{$status}")
        ->assertStatus($status)
        ->assertSee($text)
        ->assertSee('回到首頁');
})->with([
    [401, 'Unauthorized'],
    [403, 'Forbidden'],
    [404, 'Not Found'],
    [419, 'Page Expired'],
    [429, 'Too Many Requests'],
    [503, 'Service Unavailable'],
]);

it('renders the Blade 500 page when debug mode is off', function () {
    config(['app.debug' => false]);

    Route::get('/__test/errors/server-error', fn () => throw new RuntimeException('boom'));

    $this->get('/__test/errors/server-error')
        ->assertStatus(500)
        ->assertSee('Server Error')
        ->assertSee('回到首頁');
});

it('keeps returning JSON errors for JSON requests', function (int $status) {
    config(['app.debug' => false]);

    Route::get("/__test/errors/{$status}", fn () => abort($status));

    $this->getJson("/__test/errors/{$status}")
        ->assertStatus($status)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['message']);
})->with([401, 403, 404, 419, 429, 503]);

it('returns a JSON 404 for unknown JSON endpoints', function () {
    config(['app.debug' => false]);

    $this->getJson('/__test/errors/missing')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});
