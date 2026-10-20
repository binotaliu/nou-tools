<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// The timer readout the shell keeps showing after the study room page
// unmounts (resources/js/Components/StudyRoom/ShellPlayer.vue, fed by
// resources/js/Composables/useStudyRoomBackground.js).

const SHELL_SEATED_HINT = 'nou:study-room:seated:v1';

/**
 * Remembers a fresh schedule, sets a profile, takes a seat and starts a timer.
 */
$sitAndStartTimer = function (): mixed {
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Shell Timer Schedule']);

    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
    ]);

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '桌邊的人')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-root"]\') !== null');

    dismissCookieConsentBanner($page);

    $page->click('[data-testid="seat-1-S01"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-start-timer"]\') !== null');

    $page->click('[data-testid="study-room-start-timer"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-your-countdown"]\') !== null');

    return $page;
};

it('shows the timer in a pill elsewhere while the seat is held, and drops it once the seat is left', function () use ($sitAndStartTimer) {
    $page = $sitAndStartTimer();

    // The room has its own countdown, so there is no pill on it.
    $page->assertMissing('[data-testid="shell-player"]');

    $page->click('[data-testid="site-footer"] a[href="/about"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="shell-player-timer"]\') !== null');

    $page->assertVisible('[data-testid="shell-player-timer"]')
        ->assertSeeIn('[data-testid="shell-player-timer"]', '專注')
        ->assertMissing('[data-testid="shell-player-toggle"]');

    expect($page->script('/^\d+:\d\d/.test(document.querySelector(\'[data-testid="shell-player-timer"]\').textContent.replace(/\s/g, \'\').replace(\'專注\', \'\'))'))->toBeTrue();
    expect($page->script('localStorage.getItem('.json_encode(SHELL_SEATED_HINT).') !== null'))->toBeTrue();

    // Back in the room the page owns the seat again; leaving it empties the pill.
    $page->click('[data-testid="shell-player-room"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-leave-seat-running"]\') !== null');

    $page->assertMissing('[data-testid="shell-player"]')
        ->click('[data-testid="study-room-leave-seat-running"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-leave-seat-running"]\') === null');

    $page->click('[data-testid="site-footer"] a[href="/about"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="site-footer"]\') !== null && !location.pathname.startsWith(\'/study-room\')');

    $page->assertMissing('[data-testid="shell-player"]');

    expect($page->script('localStorage.getItem('.json_encode(SHELL_SEATED_HINT).') === null'))->toBeTrue();
});

it('picks the seat back up after a full page load elsewhere', function () use ($sitAndStartTimer) {
    $page = $sitAndStartTimer();

    $page->click('[data-testid="site-footer"] a[href="/about"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="shell-player-timer"]\') !== null');

    $page->navigate(route('home'));

    waitUntil($page, 'document.querySelector(\'[data-testid="shell-player-timer"]\') !== null');

    $page->assertVisible('[data-testid="shell-player-timer"]');
});
