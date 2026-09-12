<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;
use NouTools\Domains\StudyRoom\Actions\FillFloorWithTestStudents;

// The live seat map, countdown, and realtime sync are all driven
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

    // The verb select now shows short, natural picker labels (e.g. "準備考試")
    // rather than the ellipsis-based floor-map sentence template, and both
    // selects go through the shared <x-select> component. A closed native
    // <select>'s chosen <option> text isn't picked up by assertSeeIn (its
    // options have no layout box until the dropdown is open), so the
    // selected option's label is read directly instead.
    $page->assertVisible('[data-testid="study-room-verb-select"]')
        ->assertVisible('[data-testid="study-room-subject-select"]');

    $selectedVerbLabel = $page->script(
        "document.querySelector('[data-testid=\"study-room-verb-select\"]').selectedOptions[0].textContent.trim()"
    );

    expect($selectedVerbLabel)->toBe('準備考試');

    $page->click('[data-testid="study-room-start-timer"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-your-countdown"]')
        ->screenshot();
});

it('shows the PersonalInfo modal with the focus-session log when opened', function () {
    $schedule = createScheduleWithCourse();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '認真讀書中')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->waitForEvent('load');

    $page->assertVisible('[data-testid="study-room-root"]')
        ->assertMissing('[data-testid="study-room-personal-info-modal"]');

    $page->click('[data-testid="study-room-personal-info"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-personal-info-modal"]')
        ->assertVisible('[data-testid="study-room-nickname-input"]')
        ->assertVisible('[data-testid="study-room-emoji-choices"]');

    // No focus sessions exist yet for a brand-new profile, so the log
    // should render its empty state rather than staying stuck loading.
    $page->assertSeeIn(
        '[data-testid="study-room-personal-info-modal"]',
        '還沒有紀錄'
    );
});

it('shows a connection-error message once the room gives up on a realtime connection', function () {
    // This test environment may genuinely have Reverb running (a local dev
    // run does), so it can't rely on the real connect-timeout actually
    // elapsing without becoming flaky either way. Instead it drives the
    // component's own `connectionFailed`/`realtime` flags directly — the
    // exact state `connectRealtime()`'s timeout (see study-room.js) and
    // its Pusher `state_change` handler both flip on a genuine failure —
    // to deterministically exercise the resulting UI.
    $schedule = createScheduleWithCourse();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    // Let any real Reverb connection this environment can make finish
    // settling first, so setting the flags below isn't immediately
    // overwritten by a genuine `state_change` handler racing in behind it.
    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-root"]')
        ->wait(3);

    $page->script(
        "document.querySelector('[data-testid=\"study-room-page\"]')._x_dataStack[0].connectionFailed = true"
    );
    $page->script(
        "document.querySelector('[data-testid=\"study-room-page\"]')._x_dataStack[0].realtime = false"
    );

    $page->assertVisible('[data-testid="study-room-connection-error"]');
});

it('shows a popover with nickname and activity for an occupied table seat, and opens the next floor once the first is full', function () {
    $schedule = createScheduleWithCourse();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');
    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '認真讀書中')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->waitForEvent('load');

    // Fill the whole first floor with test students, which also opens
    // the second floor.
    app(FillFloorWithTestStudents::class)(1);

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-floor-1"]')
        ->assertVisible('[data-testid="study-room-floor-2"]')
        ->assertVisible('[data-testid="seat-1-T1-1-timer"]')
        ->assertMissing('[data-testid="seat-1-T1-1-popover"]')
        ->hover('[data-testid="seat-1-T1-1"]')
        ->assertVisible('[data-testid="seat-1-T1-1-popover"]')
        ->assertSeeIn('[data-testid="seat-1-T1-1-popover"]', '測試')
        ->assertSeeIn('[data-testid="study-room-floor-1"] [data-testid="study-room-stairs"]', '往二樓')
        ->assertSeeIn('[data-testid="study-room-floor-2"] [data-testid="study-room-stairs"]', '往一樓')
        ->assertSeeIn('[data-testid="study-room-floor-2"] [data-testid="study-room-stairs"]', '三樓尚未開放');
});
