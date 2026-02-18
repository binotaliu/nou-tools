<?php

use App\Models\CourseClass;

it('returns JSON and creates schedule on application/json POST', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => '測試課表',
        'items' => [$courseClass->id],
    ];

    $response = $this->postJson(route('schedule.store'), $payload);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'redirect_url'])
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('student_schedules', [
        'name' => '測試課表',
    ]);
});

it('allows creating multiple schedules within the same session token', function () {
    $courseClass = CourseClass::factory()->create();

    // simulate an existing session token
    session(['schedule_token' => 'same-session-token']);

    $payload = [
        'name' => '第一次',
        'items' => [$courseClass->id],
    ];

    $this->postJson(route('schedule.store'), $payload)->assertStatus(200);

    // same session token again
    $payload['name'] = '第二次';
    $this->postJson(route('schedule.store'), $payload)->assertStatus(200);

    $this->assertDatabaseCount('student_schedules', 2);
    $this->assertDatabaseHas('student_schedules', ['name' => '第一次']);
    $this->assertDatabaseHas('student_schedules', ['name' => '第二次']);
});
