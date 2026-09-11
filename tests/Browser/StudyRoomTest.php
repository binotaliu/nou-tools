<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Str;

// The live seat map, countdown, and realtime/polling sync are all driven
// client-side by resources/js/study-room.js (registered as the nouStudyRoom
// Alpine factory), so this behaviour is only observable with a real
// browser rather than the server-rendered Feature tests.

function createScheduleWithCourse(): StudentSchedule
{
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Study Room Schedule',
    ]);

    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
    ]);

    return $schedule;
}

it('lets a student remember their schedule, set a profile, take a seat, and start a pomodoro', function () {
    $schedule = createScheduleWithCourse();

    $page = visit(route('schedules.show', $schedule));

    // See RememberScheduleTest for why this wait matters before the
    // following form-submit navigation.
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '認真讀書中')
        // The radio inputs are visually hidden (sr-only) behind their
        // label, which is what's actually clickable — radio() targets the
        // input directly and times out waiting for it to become visible.
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->waitForEvent('load');

    $page->assertVisible('[data-testid="study-room-root"]')
        ->assertMissing('[data-testid="study-room-profile-form"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-leave-seat"]')
        ->assertSeeIn('[data-testid="seat-1-S01"]', config('study-room.emojis')[0])
        ->assertSeeIn('[data-testid="seat-1-S01"]', '認真讀書中');

    $page->click('[data-testid="study-room-start-timer"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-your-countdown"]')
        ->screenshot();
});

it('reflects a seat taken directly in the database without a page reload (the no-Reverb polling path)', function () {
    // Both intervals: in CI (and anywhere VITE_REVERB_APP_KEY isn't baked
    // into the build) window.Echo never exists and only the first interval
    // matters, but locally this repo's .env does configure Reverb, so a
    // dev run of this suite can genuinely connect — the second interval
    // keeps that "connected" safety-net poll fast too, so the assertion
    // below is deterministic either way rather than depending on which
    // path happened to be active.
    config()->set('study-room.poll.interval_seconds', 1);
    config()->set('study-room.poll.realtime_interval_seconds', 1);

    // The viewer: just browsing the room, never takes a seat themselves.
    // Remembered the normal way (through the UI) rather than injected
    // directly, since the browser test driver has no cookie-jar helper.
    $viewerSchedule = createScheduleWithCourse();

    // A different student, entirely outside this browser session, whose
    // seat is claimed directly against the database below.
    $otherSchedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($otherSchedule, 'schedule')->create([
        'nickname' => '默默讀書的人',
        'emoji' => config('study-room.emojis')[1],
    ]);

    $page = visit(route('schedules.show', $viewerSchedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-root"]')
        ->assertDontSeeIn('[data-testid="seat-1-S02"]', '默默讀書的人');

    // Someone else takes a different seat, entirely outside this browser
    // session — this is what the client's polling fallback must pick up
    // on its own, since VITE_REVERB_APP_KEY is unset in this test env and
    // window.Echo never connects.
    $seat = StudyRoomSeat::query()->where('code', '1-S02')->firstOrFail();
    $seat->update([
        'student_schedule_id' => $otherSchedule->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);

    // Give the 1-second poll loop a few cycles to catch up.
    $page->wait(5);

    $page->assertSeeIn('[data-testid="seat-1-S02"]', config('study-room.emojis')[1])
        ->assertSeeIn('[data-testid="seat-1-S02"]', '默默讀書的人');
});
