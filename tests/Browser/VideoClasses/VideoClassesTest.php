<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Carbon;

// Whether a class is "ended" is decided in the browser from its own clock, so
// this needs a real page. Dates far from now keep it independent of the time
// the suite runs at.

$videoClassAt = function (string $courseName, string $date, string $start, string $end): void {
    $class = CourseClass::factory()
        ->for(Course::factory()->create(['name' => $courseName]))
        ->create(['start_time' => $start, 'end_time' => $end]);

    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => $date]);
};

it('hides ended courses by default and reveals them with the toggle', function () use ($videoClassAt) {
    $videoClassAt('已結束的課', '2026-03-05', '19:00', '20:50');

    $page = visit(route('video-classes.index', ['date' => '2026-03-05']));

    $page->assertPresent('[data-testid="video-courses-all-ended"]')
        ->assertMissing('[data-testid="video-course-slot"]')
        ->click('[data-testid="video-courses-show-ended"]')
        ->assertSee('已結束的課')
        ->assertPresent('[data-testid="video-course-slot"]')
        ->assertMissing('[data-testid="video-courses-all-ended"]');
});

it('remembers the toggle across visits', function () use ($videoClassAt) {
    $videoClassAt('已結束的課', '2026-03-05', '19:00', '20:50');

    $page = visit(route('video-classes.index', ['date' => '2026-03-05']));
    $page->click('[data-testid="video-courses-show-ended"]')
        ->assertPresent('[data-testid="video-course-slot"]');

    $page->navigate(route('video-classes.index', ['date' => '2026-03-05']))
        ->assertPresent('[data-testid="video-course-slot"]');
});

it('never hides courses on a future date', function () use ($videoClassAt) {
    $future = Carbon::now('Asia/Taipei')->addMonths(2)->format('Y-m-d');
    $videoClassAt('未來的課', $future, '09:00', '10:50');

    visit(route('video-classes.index', ['date' => $future]))
        ->assertSee('未來的課')
        ->assertMissing('[data-testid="video-courses-all-ended"]');
});

// The badges follow the browser's real clock, so the class is placed around
// "now" in Taipei time. Near midnight the window would spill into another
// date, so those runs are skipped rather than made flaky.
$videoClassAroundNow = function (string $courseName, int $startOffsetMinutes, int $endOffsetMinutes) use ($videoClassAt): void {
    $now = Carbon::now('Asia/Taipei');
    $start = $now->copy()->addMinutes($startOffsetMinutes);
    $end = $now->copy()->addMinutes($endOffsetMinutes);

    if ($start->toDateString() !== $now->toDateString() || $end->toDateString() !== $now->toDateString()) {
        test()->markTestSkipped('Too close to midnight in Taipei.');
    }

    $videoClassAt($courseName, $now->toDateString(), $start->format('H:i'), $end->format('H:i'));
};

it('marks a class that is running as 上課中', function () use ($videoClassAroundNow) {
    $videoClassAroundNow('進行中的課', -20, 80);

    visit(route('video-classes.index'))
        ->assertSeeIn('[data-testid="video-course-status"][data-state="live"]', '上課中');
});

it('marks a class about to begin as 即將開始', function () use ($videoClassAroundNow) {
    $videoClassAroundNow('快開始的課', 15, 115);

    visit(route('video-classes.index'))
        ->assertSeeIn('[data-testid="video-course-status"][data-state="soon"]', '即將開始');
});

it('shows no badge for a class hours away', function () use ($videoClassAroundNow) {
    $videoClassAroundNow('還很久的課', 180, 280);

    visit(route('video-classes.index'))
        ->assertSee('還很久的課')
        ->assertMissing('[data-testid="video-course-status"]');
});

it('shows a "your time" hint to a viewer outside UTC+8', function () use ($videoClassAt) {
    $date = Carbon::now('Asia/Taipei')->addDays(3)->toDateString();
    $videoClassAt('海外同學的課', $date, '09:00', '10:50');

    // Asia/Kolkata is a fixed UTC+5:30, so 09:00-10:50 in Taipei is 06:30-08:20.
    visit(route('video-classes.index', ['date' => $date]))
        ->withTimezone('Asia/Kolkata')
        ->assertSeeIn('[data-testid="video-course-local-time"]', '你的時間 · 06:30 ~ 08:20 (GMT+5:30)');
});

it('shows no "your time" hint to a viewer in Taipei time', function () use ($videoClassAt) {
    $date = Carbon::now('Asia/Taipei')->addDays(3)->toDateString();
    $videoClassAt('本地同學的課', $date, '09:00', '10:50');

    visit(route('video-classes.index', ['date' => $date]))
        ->withTimezone('Asia/Taipei')
        ->assertSee('本地同學的課')
        ->assertMissing('[data-testid="video-course-local-time"]');
});

it('defaults to the 上課中 tab while a class is running', function () use ($videoClassAroundNow) {
    $videoClassAroundNow('進行中的課', -20, 80);

    visit(route('video-classes.index'))
        ->assertAttribute('[data-testid="video-courses-tab-live"]', 'aria-selected', 'true')
        ->assertSee('進行中的課');
});

it('defaults to the 即將開始 tab when nothing is running yet', function () use ($videoClassAroundNow) {
    $videoClassAroundNow('還很久的課', 180, 280);

    visit(route('video-classes.index'))
        ->assertAttribute('[data-testid="video-courses-tab-soon"]', 'aria-selected', 'true')
        ->assertSee('還很久的課');
});

it('defaults to the 所有教室 tab when nothing is running or upcoming', function () use ($videoClassAt) {
    $videoClassAt('已結束的課', '2026-03-05', '19:00', '20:50');

    visit(route('video-classes.index', ['date' => '2026-03-05']))
        ->assertAttribute('[data-testid="video-courses-tab-all"]', 'aria-selected', 'true');
});
