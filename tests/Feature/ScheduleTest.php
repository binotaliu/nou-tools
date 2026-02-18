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

    $response = $this->postJson(route('schedule.store'), $payload);

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

    $this->postJson(route('schedule.store'), $payload)->assertStatus(200);

    $payload['name'] = '第二次';
    $this->postJson(route('schedule.store'), $payload)->assertStatus(200);

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

    $response = $this->get(route('schedule.calendar', $schedule));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee($courseClass->course->name)
        ->assertSee($courseClass->code);
});
