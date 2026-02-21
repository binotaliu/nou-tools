<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a toast notification when session has success', function () {
    $response = $this->withSession(['success' => 'Saved successfully'])->get('/');

    $response->assertStatus(200);

    // the toast markup should appear with the message and position classes
    $response->assertSee('Saved successfully');
    $response->assertSee('fixed top-4 right-4');
});

it('shows first error message in a toast when validation fails', function () {
    $response = $this->withErrors(['first' => 'First error'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('First error');
    $response->assertSee('bg-red-500');
});
