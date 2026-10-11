<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\GradeCalculation;
use App\Models\StudentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

$scheduleWithCourse = function (string $term = '2025B'): array {
    $schedule = StudentSchedule::factory()->create();
    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state(['term' => $term]))
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    return [$schedule, $courseClass->course];
};

test('shows the courses with the default 30/30/40 weights', function () use ($scheduleWithCourse) {
    [$schedule, $course] = $scheduleWithCourse('2025B');

    $this->get(route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('GradeCalculator/Show')
            ->where('viewModel.isSummer', false)
            ->where('viewModel.courses.0.id', $course->id)
            ->where('viewModel.courses.0.weights', ['regular' => 30, 'midterm' => 30, 'final' => 40])
            ->where('viewModel.courses.0.scores', ['regular' => null, 'midterm' => null, 'final' => null])
        );

    expect(GradeCalculation::count())->toBe(1);
});

test('summer terms use 30/0/70 weights', function () use ($scheduleWithCourse) {
    [$schedule] = $scheduleWithCourse('2026C');

    $this->get(route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2026C']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.isSummer', true)
            ->where('viewModel.courses.0.weights', ['regular' => 30, 'midterm' => 0, 'final' => 70])
        );
});

test('returns 404 when the schedule has no courses for the term', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->get(route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->assertNotFound();

    expect(GradeCalculation::count())->toBe(0);
});

test('saves scores and custom weights', function () use ($scheduleWithCourse) {
    [$schedule, $course] = $scheduleWithCourse('2025B');
    $url = route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2025B']);
    $this->get($url);

    $this->put($url, [
        'scores' => [$course->id => ['regular' => 80, 'midterm' => 70.5, 'final' => null]],
        'weights' => [$course->id => ['regular' => 20, 'midterm' => 30, 'final' => 50]],
    ])->assertRedirect($url);

    $this->get($url)->assertInertia(fn (Assert $page) => $page
        ->where('viewModel.courses.0.scores', ['regular' => 80, 'midterm' => 70.5, 'final' => null])
        ->where('viewModel.courses.0.weights', ['regular' => 20, 'midterm' => 30, 'final' => 50])
    );
});

test('rejects invalid input', function (array $scores, array $weights, string $invalidKey) use ($scheduleWithCourse) {
    [$schedule, $course] = $scheduleWithCourse('2025B');
    $url = route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2025B']);
    $this->get($url);

    $fixId = fn (array $rows) => collect($rows)->mapWithKeys(fn ($row, $key) => [$key === 'self' ? $course->id : 999999 => $row])->all();

    $this->put($url, ['scores' => $fixId($scores), 'weights' => $fixId($weights)])
        ->assertSessionHasErrors();
})->with([
    'score above 100' => [['self' => ['final' => 101]], [], 'scores'],
    'weights not summing to 100' => [[], ['self' => ['regular' => 30, 'midterm' => 30, 'final' => 30]], 'weights'],
    'course not in the schedule' => [['other' => ['final' => 50]], [], 'scores'],
]);

test('rejects a midterm weight in a summer term', function () use ($scheduleWithCourse) {
    [$schedule, $course] = $scheduleWithCourse('2026C');
    $url = route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2026C']);
    $this->get($url);

    $this->put($url, [
        'scores' => [],
        'weights' => [$course->id => ['regular' => 30, 'midterm' => 30, 'final' => 40]],
    ])->assertSessionHasErrors();
});

$rememberedScheduleCookie = fn (StudentSchedule $schedule): string => json_encode([
    'id' => $schedule->id,
    'uuid' => $schedule->uuid,
    'name' => $schedule->name,
]);

test('/schedules/my/grade-calculator redirects to the remembered schedule for the current semester', function () use ($rememberedScheduleCookie, $scheduleWithCourse) {
    config(['app.current_semester' => '2025B']);
    [$schedule] = $scheduleWithCourse('2025B');

    $this->withCookie('student_schedule', $rememberedScheduleCookie($schedule))
        ->get(route('schedules.my.grade-calculator'))
        ->assertRedirect(route('grade-calculator.show', ['schedule' => $schedule, 'term' => '2025B']));
});

test('/schedules/my/grade-calculator falls back to the schedule without courses this semester', function () use ($rememberedScheduleCookie, $scheduleWithCourse) {
    config(['app.current_semester' => '2025B']);
    [$schedule] = $scheduleWithCourse('2024B');

    $this->withCookie('student_schedule', $rememberedScheduleCookie($schedule))
        ->get(route('schedules.my.grade-calculator'))
        ->assertRedirect(route('schedules.show', $schedule));
});

test('/schedules/my/grade-calculator redirects to the create page when no schedule is remembered', function () {
    $this->get(route('schedules.my.grade-calculator'))
        ->assertRedirect(route('schedules.create'));
});
