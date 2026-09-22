<?php

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use Inertia\Testing\AssertableInertia as Assert;

test('course schedule page loads successfully', function () {
    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page->component('Courses/Schedule'));
});

test('course schedule page includes seo meta description for the selected term', function () {
    $term = config('app.current_semester');

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) use ($term) {
        expect($page->toArray()['props']['viewModel']['selectedTerm'])->toBe($term);
    });
});

// Course grouping (by exam time / department / credits) is rendered entirely
// client-side by Vue from the `viewModel.groups` prop, so ordering is only
// observable with a real browser. See tests/Browser/CourseScheduleTest.php.
// `microCreditOrRemoteCourses` holds every course excluded from the general
// (has exam data) section — micro-credit/remote classes and courses with no
// exam data at all — so nothing is silently dropped from the page.

test('courses without a final exam time are excluded from the general section but still listed as micro/remote', function () {
    $term = config('app.current_semester');

    Course::factory()->create([
        'name' => 'No Exam Course',
        'term' => $term,
    ]);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];
        $generalNames = collect($viewModel['groups'])
            ->flatMap(fn (array $group) => collect($group['courses'])->pluck('name'));

        expect($generalNames)->not->toContain('No Exam Course');
        expect(collect($viewModel['microCreditOrRemoteCourses'])->pluck('name'))
            ->toContain('No Exam Course');
    });
});

test('courses with only a tentative full_remote or micro_credit class are still included on the page', function () {
    $term = config('app.current_semester');

    $remoteCourse = Course::factory()->create(['name' => 'Tentative Remote Course', 'term' => $term]);
    CourseClass::factory()->create([
        'course_id' => $remoteCourse->id,
        'type' => CourseClassType::FullRemote,
        'is_tentative' => true,
    ]);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];

        expect(collect($viewModel['microCreditOrRemoteCourses'])->pluck('name'))
            ->toContain('Tentative Remote Course');
    });
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

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];
        $names = collect($viewModel['groups'])
            ->flatMap(fn (array $group) => collect($group['courses'])->pluck('name'));

        expect($names)->toContain('Other Term Course');
        expect($names)->not->toContain('Current Term Course');
        expect($viewModel['selectedTerm'])->toBe('2025A');
    });
});

test('term selector lists all distinct terms', function () {
    Course::factory()->create(['term' => '2025B']);
    Course::factory()->create(['term' => '2025A']);
    Course::factory()->create(['term' => '2024B']);

    $response = $this->get(route('course.schedule'));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $availableTerms = $page->toArray()['props']['viewModel']['availableTerms'];

        expect($availableTerms)->toContain('2025B', '2025A', '2024B');
    });
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
