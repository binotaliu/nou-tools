<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// The 超大 (150%) text size from useFontSize scales every rem, so the study
// room's fixed-shape pieces (nameplate, control panel) have to restack rather
// than squeeze their text to nothing. Widths are read from the real layout.

it('keeps the nameplate and control panel usable at the largest text size on a phone', function () {
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Large Text Schedule']);

    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
    ]);

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '放大字體的人')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-root"]\') !== null');

    dismissCookieConsentBanner($page);

    $page->resize(390, 844);
    $page->script("document.documentElement.dataset.fontSize = 'xxlarge'");

    $width = fn (string $selector): int => (int) $page->script(
        "Math.round(document.querySelector('{$selector}').getBoundingClientRect().width)"
    );

    // The nickname column keeps room to read instead of collapsing beside the icons.
    expect($width('[data-testid="study-room-personal-info"] p'))->toBeGreaterThan(100);

    $page->click('[data-testid="seat-1-S01"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-leave-seat"]\') !== null');

    // Wait for the slide-in to settle, then both buttons stack at full width.
    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-control-panel"]\').getBoundingClientRect().top < innerHeight - 100');

    expect($width('[data-testid="study-room-start-timer"]'))
        ->toBe($width('[data-testid="study-room-leave-seat"]'));

    $page->click('[data-testid="study-room-start-timer"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-your-countdown"]\') !== null');

    // The countdown gets its own row, so the activity label keeps its width.
    expect($width('[data-testid="study-room-timer-activity"]'))->toBeGreaterThan(120);

    $isStacked = $page->script(
        "document.querySelector('[data-testid=\"study-room-your-countdown\"]').getBoundingClientRect().top >= document.querySelector('[data-testid=\"study-room-timer-activity\"]').getBoundingClientRect().bottom"
    );

    expect($isStacked)->toBeTrue();
});
