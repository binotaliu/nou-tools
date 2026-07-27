<?php

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;

test('course schedule page loads successfully', function () {
    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200)
        ->assertSee('本學期開課表');
});

test('courses with the same final exam weekday and time are grouped together', function () {
    $term = config('app.current_semester');
    $finalDate = now()->next('Saturday');

    $courseA = Course::factory()->create([
        'name' => 'Course A',
        'term' => $term,
        'final_date' => $finalDate,
        'exam_time_start' => '15:00',
        'exam_time_end' => '16:10',
    ]);
    $courseB = Course::factory()->create([
        'name' => 'Course B',
        'term' => $term,
        'final_date' => $finalDate,
        'exam_time_start' => '15:00',
        'exam_time_end' => '16:10',
    ]);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200)
        ->assertSeeInOrder(['15:00 - 16:10', 'Course A', 'Course B']);
});

test('courses with a full remote or micro credit class are shown together in one section, not general', function () {
    $term = config('app.current_semester');

    $remoteCourse = Course::factory()->create([
        'name' => 'Remote Course',
        'term' => $term,
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:10',
    ]);
    CourseClass::factory()->create([
        'course_id' => $remoteCourse->id,
        'type' => CourseClassType::FullRemote,
    ]);

    $microCreditCourse = Course::factory()->create([
        'name' => 'Micro Credit Course',
        'term' => $term,
    ]);
    CourseClass::factory()->create([
        'course_id' => $microCreditCourse->id,
        'type' => CourseClassType::MicroCredit,
    ]);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200)
        ->assertSeeInOrder(['微學分與全遠距', 'Micro Credit Course', 'Remote Course']);
});

test('courses without a final exam time are excluded from the general section', function () {
    $term = config('app.current_semester');

    Course::factory()->create([
        'name' => 'No Exam Course',
        'term' => $term,
    ]);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200)
        ->assertDontSee('No Exam Course');
});

test('term query parameter selects a different semester', function () {
    Course::factory()->create([
        'name' => 'Current Term Course',
        'term' => '2025B',
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:10',
    ]);
    Course::factory()->create([
        'name' => 'Other Term Course',
        'term' => '2025A',
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:10',
    ]);

    $response = $this->get(route('course.schedule', ['term' => '2025A']));

    $response->assertStatus(200)
        ->assertDontSee('Current Term Course')
        ->assertSee('Other Term Course');
});

test('term selector lists all distinct terms', function () {
    Course::factory()->create(['term' => '2025B']);
    Course::factory()->create(['term' => '2025A']);
    Course::factory()->create(['term' => '2024B']);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200)
        ->assertSee('value="2025B"', false)
        ->assertSee('value="2025A"', false)
        ->assertSee('value="2024B"', false);
});

test('markdown version of the course schedule page is available', function () {
    $term = config('app.current_semester');

    Course::factory()->create([
        'name' => 'Markdown Course',
        'term' => $term,
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:10',
    ]);

    $response = $this->get(route('course.schedule.md'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# 本學期開課表', false)
        ->assertSee('Markdown Course');
});

test('course schedule page returns markdown when the client requests it via the accept header', function () {
    $response = $this->get(route('course.schedule'), ['Accept' => 'text/markdown']);

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
});
