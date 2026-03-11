<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

uses(RefreshDatabase::class);

it('shows a toast notification when session has success', function () {
    $response = $this->withSession(['success' => 'Saved successfully'])->get('/');

    $response->assertStatus(200);

    // message must be rendered
    $response->assertSee('Saved successfully');
    // make sure the notification container is present (raw html, unescaped)
    $response->assertSee('pointer-events-none fixed inset-0', false);
});

it('shows first error message in a toast when validation fails', function () {
    // manually craft the standard error bag that ShareErrorsFromSession middleware expects
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['first' => 'First error']));

    $response = $this->withSession(['errors' => $bag])->get('/');

    $response->assertStatus(200);
    $response->assertSee('First error');
    // design now uses a red icon instead of background color
    $response->assertSee('text-red-400');
});
