<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;
use Pest\Browser\Api\PendingAwaitablePage;

// The semester `<select>` and the print button's link are built client-side, so
// the markup alone looks correct to a server-rendered Feature test whether
// or not the listeners actually attached. This regressed exactly that way
// once before, so it's checked in a real browser.

// The remember-schedule modal (see RememberScheduleTest) only shows up when
// no `student_schedule` cookie is present, and cookie state isn't guaranteed
// to be reset between test files in the same run. click() has no bounded
// wait, so blindly dismissing a modal that might not be there can hang the
// whole suite — check for it first via script().
//
// The Inertia page only mounts the modal into the DOM once client-side
// hydration completes, so a `script()` check run immediately after `visit()`
// can race it and find nothing — give it a brief moment first (mirrors the
// `->wait(1)` idiom used elsewhere for async client state, e.g.
// tests/Browser/StudyRoomTest.php).
function dismissRememberModalIfPresent(PendingAwaitablePage $page): void
{
    $page->wait(1);

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
        'name' => 'Term Switch Schedule',
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

it('links the print button to the schedule PDF for the selected term', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Print Schedule',
    ]);

    $page = visit(route('schedules.show', ['schedule' => $schedule, 'term' => '2025B']));
    dismissRememberModalIfPresent($page);

    $page->assertAttribute(
        '[data-testid="schedule-print-button"]',
        'href',
        '/schedules/'.$schedule->getRouteKey().'/print.pdf?term=2025B',
    )->assertAttribute('[data-testid="schedule-print-button"]', 'target', '_blank');
});
