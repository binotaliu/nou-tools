<?php

use App\Models\ClassSchedule;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;

it('returns JSON and creates schedule on application/json POST', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => '測試課表',
        'items' => [$courseClass->id],
    ];

    $response = $this->postJson(route('schedules.store'), $payload);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'redirect_url'])
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('student_schedules', [
        'name' => '測試課表',
    ]);
});

it('allows creating multiple schedules', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => '第一次',
        'items' => [$courseClass->id],
    ];

    $this->postJson(route('schedules.store'), $payload)->assertStatus(200);

    $payload['name'] = '第二次';
    $this->postJson(route('schedules.store'), $payload)->assertStatus(200);

    $this->assertDatabaseCount('student_schedules', 2);
    $this->assertDatabaseHas('student_schedules', ['name' => '第一次']);
    $this->assertDatabaseHas('student_schedules', ['name' => '第二次']);
});

it('returns an .ics calendar for a saved schedule', function () {
    $courseClass = CourseClass::factory()->create([
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'name' => 'ICS Export',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $courseClass->id,
    ]);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => now()->addWeek()->toDateString(),
    ]);

    $response = $this->get(route('schedules.calendar', $schedule));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee($courseClass->course->name)
        ->assertSee($courseClass->code);
});

it('stores schedule metadata in an encrypted cookie when saving', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => 'Cookie Test',
        'items' => [$courseClass->id],
    ];

    $response = $this->postJson(route('schedules.store'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $schedule = StudentSchedule::where('name', 'Cookie Test')->first();
    expect($schedule)->not->toBeNull();

    $response->assertCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => 'Cookie Test',
    ]));
});

it('shows previous schedule on home when cookie exists', function () {
    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'name' => 'Previously Saved',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('home'));

    $response->assertStatus(200)
        ->assertSee('Previously Saved')
        ->assertSee(route('schedules.show', $schedule));
});

it('shows prompt on schedule create page when cookie exists and can be ignored with ?new=1', function () {
    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'name' => 'My Old Schedule',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('schedules.create'));

    $response->assertStatus(200)
        ->assertSee('你曾建立過課表')
        ->assertSee('My Old Schedule')
        ->assertSee(route('schedules.show', $schedule));

    $response2 = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('schedules.create').'?new=1');

    $response2->assertStatus(200)
        ->assertDontSee('你曾建立過課表');
});

it('updates the stored cookie when schedule is updated', function () {
    $courseClass = CourseClass::factory()->create();

    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'name' => 'Old Name',
    ]);

    $payload = [
        'name' => 'New Name',
        'items' => [$courseClass->id],
    ];

    $response = $this->put(route('schedules.update', $schedule), $payload);

    $response->assertRedirect(route('schedules.show', $schedule));
    $response->assertCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => 'New Name',
    ]));
});

it('edit page form posts to update route and includes method spoofing', function () {
    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'name' => 'Edit Me',
    ]);

    $response = $this->get(route('schedules.edit', $schedule));

    $response->assertStatus(200)
        ->assertSee('action="'.route('schedules.update', $schedule).'"', false)
        ->assertSee('name="_method" value="PUT"', false);
});

it('create page form posts to store route and does not include method spoofing', function () {
    $response = $this->get(route('schedules.create'));

    $response->assertStatus(200)
        ->assertSee('action="'.route('schedules.store').'"', false)
        ->assertDontSee('name="_method" value="PUT"', false);
});
