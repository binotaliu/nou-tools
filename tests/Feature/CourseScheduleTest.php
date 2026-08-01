<?php

use App\Models\Course;

test('course schedule page loads successfully', function () {
    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200)
        ->assertSee('本學期開課表');
});

// Course grouping (by exam time / department / credits) and the split
// between 一般課程 and 微學分與全遠距 are rendered entirely client-side by
// Alpine from the JSON payload embedded in the page, so ordering and
// grouping are only observable with a real browser. See
// tests/Browser/CourseScheduleTest.php.

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
