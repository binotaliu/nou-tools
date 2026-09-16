<?php

use Illuminate\Support\Facades\Route;

// The Inertia Error page (resources/js/Pages/Error.vue) only renders when
// debug mode is off (see bootstrap/app.php's exceptions->respond hook) -
// with debug on, Laravel shows its own detailed exception page instead, by
// design, so local development still gets full stack traces. Tests run
// with APP_DEBUG=true by default (no .env.testing override), so debug mode
// is disabled explicitly here to exercise the real production-like path.

it('renders the shared Error page for a 404', function () {
    config(['app.debug' => false]);

    $page = visit('/this-route-does-not-exist');

    $page->assertNoJavaScriptErrors()
        ->assertVisible('[data-testid="error-page"]')
        ->assertSee('404')
        ->assertSee('Not Found');
});

it('renders the shared Error page for a 403', function () {
    config(['app.debug' => false]);

    // A single- or double-segment path would be swallowed by the existing
    // catch-all `/{type}` / `/{type}/{slug}` article routes before this
    // test route is ever reached (they're registered first, so they win
    // the match), so a three-segment path is used to sidestep them.
    Route::get('/__test/errors/forbidden', fn () => abort(403));

    $page = visit('/__test/errors/forbidden');

    $page->assertNoJavaScriptErrors()
        ->assertVisible('[data-testid="error-page"]')
        ->assertSee('403')
        ->assertSee('Forbidden');
});

it('renders the shared Error page for a 500', function () {
    config(['app.debug' => false]);

    Route::get('/__test/errors/server-error', fn () => throw new RuntimeException('boom'));

    $page = visit('/__test/errors/server-error');

    $page->assertNoJavaScriptErrors()
        ->assertVisible('[data-testid="error-page"]')
        ->assertSee('500')
        ->assertSee('Server Error');
});
