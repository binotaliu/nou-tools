<?php

use App\Models\StudentSchedule;

it('points start_url at schedules.my when no schedule token is given', function () {
    $response = $this->get(route('pwa.manifest'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJson(['start_url' => route('schedules.my', absolute: false).'?utm_source=pwa']);
});

it('points start_url at the schedule when given its short token', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->get(route('pwa.manifest', ['schedule' => $schedule->getRouteKey()]));

    $response->assertOk()
        ->assertJson(['start_url' => route('schedules.show', $schedule, absolute: false).'?utm_source=pwa']);
});

it('points start_url at the schedule when given its canonical uuid', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->get(route('pwa.manifest', ['schedule' => $schedule->uuid]));

    $response->assertOk()
        ->assertJson(['start_url' => route('schedules.show', $schedule, absolute: false).'?utm_source=pwa']);
});

it('falls back to schedules.my for an unresolvable schedule token', function () {
    $response = $this->get(route('pwa.manifest', ['schedule' => 'not-a-real-token']));

    $response->assertOk()
        ->assertJson(['start_url' => route('schedules.my', absolute: false).'?utm_source=pwa']);
});
