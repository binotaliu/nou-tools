<?php

use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

it('embeds semester data for the client-rendered greeting on the home page', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $response = $this->get('/');

    // The greeting text, date, and semester week are now computed on the
    // client from the viewer's local clock, so the server only needs to hand
    // the semester facts to the Greeting.vue component via the `greeting`
    // prop (see HomeController::index).
    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Home/Index');

        $greeting = $page->toArray()['props']['greeting'];

        expect($greeting['semesterCode'])->toBe('2025B');
        expect($greeting['semesterStart'])->toBe('2026-02-23');
        expect($greeting['semesterEnd'])->toBe('2026-07-05');
    });
});

it('omits the semester range when it is not configured', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', []);

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Home/Index');

        $greeting = $page->toArray()['props']['greeting'];

        expect($greeting['semesterStart'])->toBeNull();
        expect($greeting['semesterEnd'])->toBeNull();
    });
});
