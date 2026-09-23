<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Carbon;

// Whether a class is "ended" is decided in the browser from its own clock, so
// this needs a real page. Dates far from now keep it independent of the time
// the suite runs at.

function videoClassAt(string $courseName, string $date, string $start, string $end): void
{
    $class = CourseClass::factory()
        ->for(Course::factory()->create(['name' => $courseName]))
        ->create(['start_time' => $start, 'end_time' => $end]);

    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => $date]);
}

it('hides ended courses by default and reveals them with the toggle', function () {
    videoClassAt('已結束的課', '2026-03-05', '19:00', '20:50');

    $page = visit(route('video-classes.index', ['date' => '2026-03-05']));

    $page->assertPresent('[data-testid="video-courses-all-ended"]')
        ->assertMissing('[data-testid="video-course-slot"]')
        ->click('[data-testid="video-courses-show-ended"]')
        ->assertSee('已結束的課')
        ->assertPresent('[data-testid="video-course-slot"]')
        ->assertMissing('[data-testid="video-courses-all-ended"]');
});

it('remembers the toggle across visits', function () {
    videoClassAt('已結束的課', '2026-03-05', '19:00', '20:50');

    $page = visit(route('video-classes.index', ['date' => '2026-03-05']));
    $page->click('[data-testid="video-courses-show-ended"]')
        ->assertPresent('[data-testid="video-course-slot"]');

    $page->navigate(route('video-classes.index', ['date' => '2026-03-05']))
        ->assertPresent('[data-testid="video-course-slot"]');
});

it('never hides courses on a future date', function () {
    $future = Carbon::now('Asia/Taipei')->addMonths(2)->format('Y-m-d');
    videoClassAt('未來的課', $future, '09:00', '10:50');

    visit(route('video-classes.index', ['date' => $future]))
        ->assertSee('未來的課')
        ->assertMissing('[data-testid="video-courses-all-ended"]');
});

// The badges follow the browser's real clock, so the class is placed around
// "now" in Taipei time. Near midnight the window would spill into another
// date, so those runs are skipped rather than made flaky.
function videoClassAroundNow(string $courseName, int $startOffsetMinutes, int $endOffsetMinutes): void
{
    $now = Carbon::now('Asia/Taipei');
    $start = $now->copy()->addMinutes($startOffsetMinutes);
    $end = $now->copy()->addMinutes($endOffsetMinutes);

    if ($start->toDateString() !== $now->toDateString() || $end->toDateString() !== $now->toDateString()) {
        test()->markTestSkipped('Too close to midnight in Taipei.');
    }

    videoClassAt($courseName, $now->toDateString(), $start->format('H:i'), $end->format('H:i'));
}

it('marks a class that is running as 上課中', function () {
    videoClassAroundNow('進行中的課', -20, 80);

    visit(route('video-classes.index'))
        ->assertSeeIn('[data-testid="video-course-status"][data-state="live"]', '上課中');
});

it('marks a class about to begin as 即將開始', function () {
    videoClassAroundNow('快開始的課', 15, 115);

    visit(route('video-classes.index'))
        ->assertSeeIn('[data-testid="video-course-status"][data-state="soon"]', '即將開始');
});

it('shows no badge for a class hours away', function () {
    videoClassAroundNow('還很久的課', 180, 280);

    visit(route('video-classes.index'))
        ->assertSee('還很久的課')
        ->assertMissing('[data-testid="video-course-status"]');
});
