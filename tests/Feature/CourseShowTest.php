<?php

use App\Models\Course;
use App\Models\CourseClass;

test('course show page loads successfully', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'credits' => 2,
        'department' => 'Test Department',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('Test Course')
        ->assertSee('Test Department');
});

test('course show page displays course information', function () {
    $course = Course::factory()->create([
        'name' => 'Advanced Testing',
        'credit_type' => '必修',
        'credits' => 3,
        'department' => 'Computer Science',
        'in_person_class_type' => '四次',
        'media' => '網頁',
        'nature' => '進階',
        'description_url' => 'https://example.com/description',
        'multimedia_url' => 'https://example.com/multimedia',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('Advanced Testing')
        ->assertSee('必修')
        ->assertSee('3 學分')
        ->assertSee('Computer Science')
        ->assertSee('四次')
        ->assertSee('網頁')
        ->assertSee('進階');
});

test('course show page displays course classes', function () {
    $course = Course::factory()->create();
    $class = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'TEST001',
        'teacher_name' => '王老師',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'link' => 'https://example.com/class',
    ]);
    $class->schedules()->create([
        'date' => now()->addDays(7),
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('TEST001')
        ->assertSee('王')
        ->assertSee('老師')
        ->assertSee('09:00 - 11:00')
        ->assertDontSee('~');
});

test('schedule-level overrides show next to dates only', function () {
    $course = Course::factory()->create();
    $class = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'OVR001',
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);

    $dateWithOverride = now()->addDays(3);
    $dateWithoutOverride = now()->addDays(4);

    $class->schedules()->create([
        'date' => $dateWithOverride,
        'start_time' => '14:00',
        'end_time' => '16:00',
    ]);

    $class->schedules()->create([
        'date' => $dateWithoutOverride,
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('09:00 - 11:00')
        ->assertSee($dateWithOverride->format('n/j'))
        ->assertSee('14:00 - 16:00')
        ->assertSee($dateWithoutOverride->format('n/j'))
        ->assertDontSee('~');
});

test('course show page with missing schedule information', function () {
    $course = Course::factory()->create();
    CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'EMPTY001',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('EMPTY001')
        ->assertSee('未設定上課時間');
});

test('course show page without classes', function () {
    $course = Course::factory()->create([
        'name' => 'Standalone Course',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('Standalone Course');
});

test('course show page shows previous-schedule link when cookie exists', function () {
    $course = Course::factory()->create();

    $schedule = \App\Models\StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'name' => 'My Saved Schedule',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('回到我的課表')
        ->assertSee(route('schedules.show', $schedule));
});

test('course show page displays exam information', function () {
    $course = Course::factory()->create([
        'name' => 'Exam Course',
        'midterm_date' => '2025-04-25',
        'final_date' => '2025-06-27',
        'exam_time_start' => '13:30',
        'exam_time_end' => '14:40',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200)
        ->assertSee('期中考')
        ->assertSee('期末考')
        ->assertSee('4/25')
        ->assertSee('6/27')
        ->assertSee('13:30 - 14:40');
});
