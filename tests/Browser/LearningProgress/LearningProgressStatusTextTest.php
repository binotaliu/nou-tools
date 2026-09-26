<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;

// WCAG 1.4.1: the current and overdue weeks are tinted blue/red, so each row
// header also says which one it is in words.

afterEach(fn () => Carbon::setTestNow());

it('labels the current and overdue weeks in words, not only by tint', function () {
    config()->set('app.current_semester', '2025B');
    config()->set('app.current_semester_range', ['2026-02-23', '2026-06-28']);
    Carbon::setTestNow('2026-03-09 09:00:00'); // week 3

    $schedule = StudentSchedule::factory()->create();
    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state(['term' => '2025B', 'name' => '國文賞析']))
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']))->resize(1280, 900);

    waitUntil($page, "document.querySelector('[data-testid=\"week-status\"]') !== null");

    $labels = json_decode($page->script("JSON.stringify([...document.querySelectorAll('[data-testid=\"week-status\"]')].map(e => e.textContent.trim()))"), true);

    expect(array_count_values($labels))->toBe(['進度落後' => 2, '目前週次' => 1]);
});
