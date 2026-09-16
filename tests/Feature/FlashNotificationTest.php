<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// The toast itself is rendered client-side by AppLayout.vue's Notification.vue
// (see HandleInertiaRequests::share for the `flash.success` prop; `errors` is
// Inertia's own default shared prop), so these only assert the server hands
// the right raw data through.

it('shares a success flash message for the toast notification', function () {
    $response = $this->withSession(['success' => 'Saved successfully'])->get('/');

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        expect($page->toArray()['props']['flash']['success'])->toBe('Saved successfully');
    });
});

it('shares the first validation error for the toast notification', function () {
    // manually craft the standard error bag that ShareErrorsFromSession middleware expects
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['first' => 'First error']));

    $response = $this->withSession(['errors' => $bag])->get('/');

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        expect(array_values($page->toArray()['props']['errors']))->toContain('First error');
    });
});
