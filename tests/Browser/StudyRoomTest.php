<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Carbon\CarbonImmutable;
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
        ->assertSeeIn('[data-testid="study-room-floor-2"] [data-testid="study-room-stairs"]', '往三樓')
        ->assertVisible('[data-testid="study-room-floor-2"] [data-testid="study-room-stair-blocked"]');
});

it('updates a floor\'s occupied count live and closes it once its last occupant leaves', function () {
    // Regression test for two bugs in applyDelta()/patchSeat(): the
    // per-floor "N / N 人在座" badge never updated from a realtime seat
    // patch (only a full setState() touched floor.occupiedCount), and a
    // floor that closed (openFloors shrinking) was never dropped from
    // state.floors, leaving a stale empty floor rendered forever. Neither
    // is reachable through a real second Reverb-connected browser in this
    // test environment, so the fix is exercised by feeding applyDelta()
    // the same payload shapes StudyRoomUpdated::broadcastWith() produces.
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
        ->assertSeeIn('[data-testid="study-room-floor-1"]', '24 / 24 人在座');

    $emptySeatPayload = static fn (string $code): string => json_encode([
        'code' => $code,
        'kind' => 'solo',
        'groupCode' => null,
        'seatNumber' => 1,
        'label' => $code,
        'isOccupied' => false,
        'isYou' => false,
        'nickname' => null,
        'emoji' => null,
        'activity' => null,
        'timerMode' => null,
        'timerPhase' => null,
        'timerEndsAt' => null,
        'timerStartedAt' => null,
    ]);

    // One seat leaving floor 1 shouldn't require a full refresh to show up
    // in the floor's occupied count.
    $page->script(
        'document.querySelector(\'[data-testid="study-room-page"]\')._x_dataStack[0].applyDelta('.
            '{openFloors: 2, totals: {occupantCount: 23, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "v2", seat: '.
            $emptySeatPayload('1-S01').
            '})'
    );

    $page->assertSeeIn('[data-testid="study-room-floor-1"]', '23 / 24 人在座');

    // Once floor 1 is no longer full, floor 2 (which nobody ever sat in)
    // should close and disappear entirely, not linger with stale data.
    $page->script(
        'document.querySelector(\'[data-testid="study-room-page"]\')._x_dataStack[0].applyDelta('.
            '{openFloors: 1, totals: {occupantCount: 22, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "v3", seat: '.
            $emptySeatPayload('1-S02').
            '})'
    );

    $page->assertMissing('[data-testid="study-room-floor-2"]');
});

it('draws the garden and windows from the real Taiwan sky, day and night', function () {
    // The sky is computed client-side from the campus coordinates (see
    // study-room-sky.js); previewSky() freezes it at a chosen instant so
    // the test doesn't depend on when it runs.
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

    $page->assertVisible('[data-testid="study-room-floor-1"] [data-testid="study-room-garden"]')
        ->assertVisible('[data-testid="study-room-floor-1"] [data-testid="study-room-windows"]')
        ->assertVisible('[data-testid="study-room-floor-1"] [data-testid="study-room-sky-canvas"]');

    $component = 'document.querySelector(\'[data-testid="study-room-page"]\')._x_dataStack[0]';

    // The sky is painted by a WebGL shader layered over the CSS gradient
    // (study-room-sky-shader.js). Reading the framebuffer back is the only
    // way to tell it really painted rather than sitting there transparent —
    // and where WebGL isn't available it *is* transparent by design, with
    // the gradient showing through, so the colour checks below only apply
    // when the renderer actually came up.
    $shaderPainting = $page->script($component.'.skyCanvasActive');
    $sampleSky = static fn (): string => $component.'.skyRenderer.sample(0.15, 0.12)';

    $previewSky = static fn (string $iso): string => $component.'.previewSky('.
        (CarbonImmutable::parse($iso)->getTimestampMs()).')';

    // Solstice noon over Luzhou: the sun is almost overhead. The wait has
    // to clear the garden's 1000ms colour transitions, not just start them.
    $page->script($previewSky('2026-06-21T12:00:00+08:00'));
    $page->wait(2);

    $page->assertAttribute('[data-testid="study-room-garden"]', 'data-sky-phase', 'day')
        ->assertAttributeContains('[data-testid="study-room-garden"]', 'aria-label', '白天')
        ->assertVisible('[data-testid="study-room-sun"]');

    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"study-room-stars\"]')).opacity"))
        ->toBe('0');

    if ($shaderPainting) {
        // Midday sky, sampled high and away from the sun: blue, and bright.
        [$red, , $blue] = $page->script($sampleSky());

        expect($blue)->toBeGreaterThan(150)
            ->and($blue)->toBeGreaterThan($red + 40);
    }

    // The night of the June 2026 full moon: no sun, stars out, moon full.
    $page->script($previewSky('2026-06-29T23:00:00+08:00'));
    $page->wait(2);

    $page->assertAttribute('[data-testid="study-room-garden"]', 'data-sky-phase', 'night')
        ->assertAttributeContains('[data-testid="study-room-garden"]', 'aria-label', '月亮100% 亮')
        ->assertMissing('[data-testid="study-room-sun"]')
        ->assertVisible('[data-testid="study-room-moon"]');

    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"study-room-stars\"]')).opacity"))
        ->toBe('1');

    if ($shaderPainting) {
        // Same point under the same shader eight hours later: near black.
        [$red, $green, $blue] = $page->script($sampleSky());

        expect($red + $green + $blue)->toBeLessThan(120);
    }

    // The window panes carry the sky's horizon colour rather than a fixed tint.
    expect($page->script("document.querySelector('[data-testid=\"study-room-windows\"] span').style.background"))
        ->toContain('rgb');
});
