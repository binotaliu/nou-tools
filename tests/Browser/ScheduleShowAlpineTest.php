<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;
use Pest\Browser\Api\PendingAwaitablePage;

// The semester `<select>` and the print button on schedule/show.blade.php sit
// outside any `x-data` scope, so Alpine's CSP build (which only walks trees
// rooted at [x-data]) previously never attached their @change/@click
// listeners at all — this was invisible to server-rendered Feature tests
// since the markup itself is correct; only a real browser exposes it.

// The remember-schedule modal (see RememberScheduleTest) only shows up when
// no `student_schedule` cookie is present, and cookie state isn't guaranteed
// to be reset between test files in the same run. click() has no bounded
// wait, so blindly dismissing a modal that might not be there can hang the
// whole suite — check for it first via script(), which returns immediately.
function dismissRememberModalIfPresent(PendingAwaitablePage $page): void
{
    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }
}

it('submits the term form and navigates when a different semester is selected', function () {
    config()->set('app.current_semester', '2026C');

    $currentCourse = Course::factory()->create(['term' => '2026C']);
    $currentClass = CourseClass::factory()->create(['course_id' => $currentCourse->id]);

    $otherCourse = Course::factory()->create(['term' => '2025B']);
    $otherClass = CourseClass::factory()->create(['course_id' => $otherCourse->id]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Alpine Term Switch Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $currentCourse->id,
        'course_class_id' => $currentClass->id,
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $otherCourse->id,
        'course_class_id' => $otherClass->id,
    ]);

    $page = visit(route('schedules.show', $schedule));
    dismissRememberModalIfPresent($page);

    // select() submits the form (@change="$event.target.form.submit()"),
    // which navigates the page. Give that navigation a moment to land before
    // reading the URL — and don't screenshot() here, since racing the
    // in-flight navigation can hang the browser driver.
    $page->select('#term', '2025B')
        ->wait(1);

    expect($page->url())->toContain('term=2025B');
});

it('calls window.print() when the print button is clicked', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Alpine Print Schedule',
    ]);

    $page = visit(route('schedules.show', $schedule));
    dismissRememberModalIfPresent($page);

    $page->script('window.__printed = false; window.print = () => { window.__printed = true; };');
    $page->click('[data-testid="schedule-print-button"]');

    expect($page->script('window.__printed'))->toBe(true);
});
