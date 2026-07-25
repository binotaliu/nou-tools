<?php

use Illuminate\Support\Facades\Config;

it('embeds semester data for the client-rendered greeting on the home page', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $response = $this->get('/');

    // The greeting text, date, and semester week are now computed on the
    // client from the viewer's local clock, so the server only needs to hand
    // the semester facts to the Alpine component.
    $response->assertStatus(200)
        ->assertSee('nouGreeting(', false)
        ->assertSee('今天是')
        ->assertSee('2025B', false)
        ->assertSee('2026-02-23', false)
        ->assertSee('2026-07-05', false);
});

it('omits the semester range when it is not configured', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', []);

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('nouGreeting(', false)
        ->assertSee('semesterStart: null', false)
        ->assertSee('semesterEnd: null', false);
});
