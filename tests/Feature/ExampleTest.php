<?php

use Inertia\Testing\AssertableInertia as Assert;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders the shared Inertia Error page for a 404', function () {
    // Error pages only render through Inertia when debug mode is off (see
    // bootstrap/app.php); with debug on, Laravel's own exception page is
    // shown instead, which is what local development should see.
    config(['app.debug' => false]);

    $response = $this->get('/ThisRouteDoesNotExist');

    $response->assertStatus(404);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->where('status', 404)
    );
});

it('returns the llms.txt markdown content when the client prefers markdown on the home page', function () {
    $response = $this->get('/', [
        'Accept' => 'text/markdown, text/html;q=0.8',
    ]);

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# NOU 小幫手', false);
});
