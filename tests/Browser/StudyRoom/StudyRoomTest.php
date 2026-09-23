<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use NouTools\Domains\StudyRoom\Actions\FillFloorWithTestStudents;

// The live seat map, countdown, and realtime sync are all driven
// client-side by resources/js/Pages/StudyRoom/Show.vue and its composables
// (resources/js/Composables/useStudyRoomSocket.js, useSeatGrid.js,
// useStudyTimer.js, useStudyRoomSky.js, useStudyRoomProfile.js), so this
// behaviour is only observable with a real browser rather than the
// server-rendered Feature tests. Low-level assertions that used to reach
// into Alpine's `_x_dataStack[0]` now go through `window.__studyRoomTest`,
// a debug bridge the page exposes onMounted (see Show.vue).

/**
 * Pulls the lamp's shade opening and bulb out of the rendered page. Moved
 * here from the old tests/Feature/StudyRoom/DeskLampTest.php, which
 * asserted on server-rendered Blade markup — the lamp is now client-rendered
 * by Vue, so this geometry check needs a real browser.
 *
 * @return array<string, array{cx: float, cy: float, r: float}>
 */
function studyRoomLampParts(string $html): array
{
    $parts = [];

    foreach (['shade-mouth', 'bulb'] as $part) {
        preg_match_all(
            '/<(?:ellipse|circle)\b[^>]*data-testid="study-room-lamp-'.$part.'"[^>]*>/s',
            $html,
            $tags
        );

        foreach ($tags[0] as $tag) {
            $attribute = function (string $name) use ($tag): float {
                preg_match('/\b'.$name.'="([\d.]+)"/', $tag, $value);

                return (float) ($value[1] ?? 0);
            };

            $parts[$part][] = [
                'cx' => $attribute('cx'),
                'cy' => $attribute('cy'),
                'r' => $attribute('r') ?: min($attribute('rx'), $attribute('ry')),
            ];
        }
    }

    return $parts;
}

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
        // Only asserted, never clicked: ticking it asks for notification
        // permission, which a headless browser answers unpredictably. It must
        // be enabled, though: the push composable's refs once reached the
        // form un-unwrapped, leaving it permanently disabled.
        ->assertVisible('[data-testid="study-room-notify-checkbox"]')
        ->assertEnabled('[data-testid="study-room-notify-checkbox"]')
        ->fill('nickname', '認真讀書中')
        // The radio inputs are visually hidden (sr-only) behind their
        // label, which is what's actually clickable — radio() targets the
        // input directly and times out waiting for it to become visible.
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->wait(1);

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->assertMissing('[data-testid="study-room-profile-form"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1);

    // Twemoji swaps the emoji character for an <img alt="..."> once it loads,
    // so the seat's chosen emoji is checked via source (it survives the swap
    // in the alt attribute) rather than assertSeeIn's visible-text search.
    $page->assertVisible('[data-testid="study-room-leave-seat"]')
        ->assertSourceInHas('[data-testid="seat-1-S01"]', config('study-room.emojis')[0])
        ->assertSeeIn('[data-testid="seat-1-S01"]', '認真讀書中');

    // The verb picker is a group of large icon buttons (radio inputs behind
    // sr-only labels) showing short, natural picker labels (e.g. "準備考試")
    // rather than the ellipsis-based floor-map sentence template.
    $page->assertVisible('[data-testid="study-room-verb-group"]')
        ->assertVisible('[data-testid="study-room-subject-select"]');

    $isExamPrepChecked = $page->script(
        "document.querySelector('[data-testid=\"study-room-verb-exam_prep\"]').checked"
    );

    expect($isExamPrepChecked)->toBeTrue();

    $page->click('[data-testid="study-room-start-timer"]')
        ->wait(1);

    // The banner flips from the start form to the countdown view: the
    // pomodoro's progress bar, round dots and round label all appear.
    $page->assertVisible('[data-testid="study-room-your-countdown"]')
        ->assertMissing('[data-testid="study-room-timer-form"]')
        ->assertVisible('[data-testid="study-room-progress-bar"]')
        ->assertVisible('[data-testid="study-room-cycle-dots"]')
        ->assertSeeIn('[data-testid="study-room-round-label"]', '第 1 輪')
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '專注中')
        ->screenshot();
});

it('prepends the countdown and phase to the tab title, and swaps the favicon, once backgrounded', function () {
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
        ->wait(1);

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1)
        ->click('[data-testid="study-room-start-timer"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-your-countdown"]');

    $originalTitle = $page->script('document.title');
    $originalIcoHref = $page->script(
        'document.getElementById(\'favicon-ico\').getAttribute(\'href\')'
    );

    // document.hidden has no setter, so backgrounding is faked the same way
    // real Page Visibility changes surface: override the getter, then fire
    // the event the component actually listens for.
    $page->script(
        'Object.defineProperty(document, "hidden", { configurable: true, get: () => true }); '.
        'document.dispatchEvent(new Event("visibilitychange"));'
    );
    $page->wait(1);

    $title = $page->script('document.title');
    $icoHref = $page->script(
        'document.getElementById(\'favicon-ico\').getAttribute(\'href\')'
    );
    $svgRel = $page->script(
        'document.getElementById(\'favicon-svg\').getAttribute(\'rel\')'
    );

    expect($title)->not->toBe($originalTitle)
        ->and($title)->toContain($originalTitle)
        ->and($title)->toMatch('/^\d{2}:\d{2} 專注中/')
        ->and($icoHref)->not->toBe($originalIcoHref)
        ->and($icoHref)->toStartWith('data:image/png')
        ->and($svgRel)->toBe('alternate icon');

    // Foregrounding again restores everything exactly.
    $page->script(
        'Object.defineProperty(document, "hidden", { configurable: true, get: () => false }); '.
        'document.dispatchEvent(new Event("visibilitychange"));'
    );
    $page->wait(1);

    expect($page->script('document.title'))->toBe($originalTitle)
        ->and($page->script(
            'document.getElementById(\'favicon-ico\').getAttribute(\'href\')'
        ))->toBe($originalIcoHref)
        ->and($page->script(
            'document.getElementById(\'favicon-svg\').getAttribute(\'rel\')'
        ))->toBe('icon');

    // Once the round finishes, remainingLabel() switches to the "+MM:SS"
    // overtime form. That title must still be recognised as "ours" on the
    // next tick, or captureOriginalTitle() treats it as the real page title
    // and every subsequent tick prepends another "+MM:SS 這一輪完成了 - "
    // segment onto it forever.
    $seat = StudyRoomSeat::query()->where('student_schedule_id', $schedule->id)->sole();
    $seat->update(['timer_ends_at' => now()->subSecond()]);

    $page->script('window.__studyRoomTest.socket.refresh()');
    $page->wait(1);
    $page->script(
        'Object.defineProperty(document, "hidden", { configurable: true, get: () => true }); '.
        'document.dispatchEvent(new Event("visibilitychange"));'
    );
    $page->wait(3);

    $overtimeTitle = $page->script('document.title');

    expect($overtimeTitle)->toMatch('/^\+\d{2}:\d{2} 這一輪完成了 - /')
        ->and($overtimeTitle)->toContain($originalTitle)
        ->and(substr_count($overtimeTitle, '這一輪完成了'))->toBe(1);
});

it('lets a student tune their pomodoro cycle and walks them through break and next round', function () {
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
        ->wait(1);

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-timer-form"]');

    // A two-round cycle, so the long break comes up quickly.
    $page->click('[data-testid="study-room-cycle-settings"]')
        ->assertVisible('[data-testid="study-room-cycle-modal"]')
        ->fill('[data-testid="study-room-cycle-rounds"]', '2')
        ->fill('[data-testid="study-room-cycle-long-break"]', '20')
        ->assertSeeIn('[data-testid="study-room-cycle-summary"]', '每 2 輪長休 20 分')
        ->click('[data-testid="study-room-cycle-done"]')
        ->assertMissing('[data-testid="study-room-cycle-modal"]');

    $page->click('[data-testid="study-room-start-timer"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-round-label"]', '第 1 輪');

    expect($page->script('document.querySelectorAll(\'[data-testid="study-room-cycle-dots"] span\').length'))->toBe(2);

    // The cycle was saved on the profile, not just kept in the browser.
    $profile = StudyRoomProfile::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($profile->pomodoro_rounds_per_cycle)->toBe(2)
        ->and($profile->pomodoro_long_break_minutes)->toBe(20);

    // Wind the focus timer down to its end server-side (the browser can't
    // wait 25 real minutes) and let the component pick the change up.
    $seat = StudyRoomSeat::query()->where('student_schedule_id', $schedule->id)->sole();
    $seat->update(['timer_ends_at' => now()->subSecond()]);

    $component = 'window.__studyRoomTest.socket';

    $page->script($component.'.refresh()');
    $page->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '這一輪完成了')
        ->assertVisible('[data-testid="study-room-start-break"]')
        ->click('[data-testid="study-room-start-break"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '休息一下')
        ->assertVisible('[data-testid="study-room-next-round"]')
        ->assertSeeIn('[data-testid="study-room-next-round"]', '跳過休息');

    // Cutting the break short goes straight into round 2.
    $page->click('[data-testid="study-room-next-round"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-round-label"]', '第 2 輪')
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '專注中')
        ->assertMissing('[data-testid="study-room-next-round"]');

    // Round 2 is the last of the cycle, so its break is the long one.
    $seat->refresh();
    $seat->update(['timer_ends_at' => now()->subSecond()]);
    $page->script($component.'.refresh()');
    $page->wait(1)
        ->assertSeeIn('[data-testid="study-room-start-break"]', '開始休息')
        ->click('[data-testid="study-room-start-break"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '長休息');

    $seat->refresh();
    expect((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(20);
});

it('opens a fullscreen focus mode over the sky and leaves it when the timer stops', function () {
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
        ->wait(1);

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1)
        // Focus mode needs a running timer, so the button isn't offered yet.
        ->assertMissing('[data-testid="study-room-focus-mode-open"]')
        ->click('[data-testid="study-room-start-timer"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-focus-mode-open"]')
        ->assertMissing('[data-testid="study-room-focus-mode"]');

    $page->click('[data-testid="study-room-focus-mode-open"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-focus-mode"]')
        ->assertVisible('[data-testid="study-room-focus-sky-canvas"]')
        ->assertVisible('[data-testid="study-room-focus-countdown"]')
        ->assertVisible('[data-testid="study-room-focus-progress-bar"]')
        ->assertSeeIn('[data-testid="study-room-focus-activity"]', '準備')
        ->assertSeeIn('[data-testid="study-room-focus-phase"]', '專注中')
        ->assertSeeIn('[data-testid="study-room-focus-mode"]', '認真讀書中');

    // Regression check (formerly a Feature test asserting on server-rendered
    // Blade markup — the lamp is now client-rendered, so it needs a real
    // browser): the desk lamp's bulb has to fit inside its shade opening in
    // all three places it's drawn (the "start timer" form, the running
    // timer panel, and focus mode) — the old lamp hung the bulb off the
    // outside edge of the shade.
    $lampHtml = $page->script(
        'document.querySelector(\'[data-testid="study-room-page"]\').outerHTML'
    );
    $lampParts = studyRoomLampParts($lampHtml);

    expect($lampParts['bulb'])->toHaveCount(3);

    foreach ($lampParts['bulb'] as $index => $bulb) {
        $mouth = $lampParts['shade-mouth'][$index];

        expect($bulb['r'])->toBeGreaterThan(0);
        expect($mouth['r'])->toBeGreaterThan(0);

        $offset = sqrt(($bulb['cx'] - $mouth['cx']) ** 2 + ($bulb['cy'] - $mouth['cy']) ** 2);

        expect($offset + $bulb['r'])->toBeLessThanOrEqual($mouth['r']);
    }

    // The clock in focus mode is the same clock as the page's.
    $focusClock = $page->script('document.querySelector(\'[data-testid="study-room-focus-clock"]\').textContent.trim()');
    expect($focusClock)->toMatch('/^\d{2}:\d{2}$/');

    $page->screenshot(filename: 'study-room-focus-mode');

    // Esc leaves focus mode; the timer keeps running.
    $page->keys('[data-testid="study-room-focus-mode"]', ['Escape'])
        ->wait(1)
        ->assertMissing('[data-testid="study-room-focus-mode"]')
        ->assertVisible('[data-testid="study-room-your-countdown"]');

    // The phone layout still fits the banner, and focus mode's countdown
    // and controls, on screen. (Resized here rather than while focus mode
    // is open: opening it also asks for the browser's real fullscreen,
    // and a fullscreen window can't be resized.)
    $page->resize(390, 844)
        ->wait(1)
        ->assertVisible('[data-testid="study-room-your-countdown"]')
        ->assertVisible('[data-testid="study-room-focus-mode-open"]')
        ->screenshot(filename: 'study-room-banner-mobile')
        ->click('[data-testid="study-room-focus-mode-open"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-focus-mode"]')
        ->assertVisible('[data-testid="study-room-focus-countdown"]')
        ->assertVisible('[data-testid="study-room-focus-stop-timer"]')
        ->screenshot(filename: 'study-room-focus-mode-mobile');

    // Everything in focus mode sits on screen, and the countdown and buttons
    // stay clear of the window above them.
    $layout = $page->script(<<<'JS'
        (() => {
            const rect = (id) => document
                .querySelector(`[data-testid="${id}"]`)
                .getBoundingClientRect();
            const window = rect('study-room-focus-window');
            const stop = rect('study-room-focus-stop-timer');

            return {
                countdownTop: rect('study-room-focus-countdown').top,
                windowBottom: window.bottom,
                stopBottom: stop.bottom,
                stopRight: stop.right,
                viewportHeight: innerHeight,
                viewportWidth: innerWidth,
            };
        })()
        JS);

    expect($layout['countdownTop'])->toBeGreaterThan($layout['windowBottom']);
    expect($layout['stopBottom'])->toBeLessThan($layout['viewportHeight']);
    expect($layout['stopRight'])->toBeLessThanOrEqual($layout['viewportWidth']);

    // Stopping the timer from inside focus mode closes it as well.
    $page->click('[data-testid="study-room-focus-stop-timer"]')
        ->wait(1)
        ->assertMissing('[data-testid="study-room-focus-mode"]')
        ->assertVisible('[data-testid="study-room-timer-form"]');
});

it('minimizes the action banner to a slim bar and expands it again', function () {
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
        ->wait(1);

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-timer-form"]')
        ->assertMissing('[data-testid="study-room-banner-expand"]')
        ->click('[data-testid="study-room-banner-minimize"]')
        ->assertMissing('[data-testid="study-room-timer-form"]')
        ->assertVisible('[data-testid="study-room-banner-expand"]');

    // The choice is remembered across a reload.
    $page->navigate(route('study-room.show'))
        ->wait(1)
        ->assertMissing('[data-testid="study-room-timer-form"]')
        ->click('[data-testid="study-room-banner-expand"]')
        ->assertVisible('[data-testid="study-room-timer-form"]')
        ->click('[data-testid="study-room-start-timer"]')
        ->wait(1)
        ->click('[data-testid="study-room-banner-minimize"]')
        ->assertMissing('[data-testid="study-room-timer-panel"]')
        ->assertVisible('[data-testid="study-room-banner-mini-countdown"]')
        ->click('[data-testid="study-room-banner-expand"]')
        ->assertVisible('[data-testid="study-room-your-countdown"]');
});

it('shows the PersonalInfo modal for editing nickname/emoji, without the session log', function () {
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
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->assertMissing('[data-testid="study-room-personal-info-modal"]');

    $page->click('[data-testid="study-room-personal-info"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-personal-info-modal"]')
        ->assertVisible('[data-testid="study-room-nickname-input"]')
        ->assertVisible('[data-testid="study-room-emoji-choices"]')
        ->assertMissing('[data-testid="study-room-stats-session-log"]');
});

it('shows the Stats modal with the 7-day chart and an empty-state log when opened', function () {
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
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="study-room-personal-info-stats"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-stats-modal"]')
        ->assertVisible('[data-testid="study-room-stats-chart"]');

    // No focus sessions exist yet for a brand-new profile, so today's log
    // should render its empty state rather than staying stuck loading.
    $page->assertSeeIn(
        '[data-testid="study-room-stats-modal"]',
        '這天沒有紀錄'
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

    $page->script('window.__studyRoomTest.socket.connectionFailed = true');
    $page->script('window.__studyRoomTest.socket.realtime = false');

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
        ->wait(1);

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

it('formats a seat timer as mm:ss under an hour and h:mm:ss from an hour onward', function () {
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
        ->wait(1);

    $component = 'window.__studyRoomTest.timer';

    expect($page->script($component.'.clockLabel(59)'))->toBe('00:59')
        ->and($page->script($component.'.clockLabel(3599)'))->toBe('59:59')
        ->and($page->script($component.'.clockLabel(3600)'))->toBe('1:00:00')
        ->and($page->script($component.'.clockLabel(3661)'))->toBe('1:01:01')
        ->and($page->script($component.'.clockLabel(7325)'))->toBe('2:02:05');
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
        ->wait(1);

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
        'window.__studyRoomTest.socket.applyDelta('.
            '{openFloors: 2, totals: {occupantCount: 23, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "v2", seat: '.
            $emptySeatPayload('1-S01').
            '})'
    );

    $page->assertSeeIn('[data-testid="study-room-floor-1"]', '23 / 24 人在座');

    // Once floor 1 is no longer full, floor 2 (which nobody ever sat in)
    // should close and disappear entirely, not linger with stale data.
    $page->script(
        'window.__studyRoomTest.socket.applyDelta('.
            '{openFloors: 1, totals: {occupantCount: 22, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "v3", seat: '.
            $emptySeatPayload('1-S02').
            '})'
    );

    $page->assertMissing('[data-testid="study-room-floor-2"]');
});

it('clears the held-seat highlight and action banner once a realtime delta releases your own seat', function () {
    // Regression test for patchSeat() not clearing heldSeatCode when the
    // viewer's own seat is released by something other than a heartbeat
    // response (idle kick, admin clear, etc.) — the seat kept its "this is
    // mine" amber highlight and the action banner stayed visible until the
    // next heartbeat poll or full refresh happened to catch up.
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
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-control-panel"]');

    $isHighlighted = $page->script(
        "document.querySelector('[data-testid=\"seat-1-S01\"]').className.includes('amber')"
    );

    expect($isHighlighted)->toBeTrue();

    // Same payload shape RecordHeartbeat/ReleaseIdleSeats/an admin clear
    // broadcasts for a seat that's no longer occupied.
    $page->script(
        'window.__studyRoomTest.socket.applyDelta('.
            '{openFloors: 1, totals: {occupantCount: 0, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "v2", seat: '.
            json_encode([
                'code' => '1-S01',
                'kind' => 'solo',
                'groupCode' => null,
                'seatNumber' => 1,
                'label' => '1-S01',
                'isOccupied' => false,
                'isYou' => false,
                'nickname' => null,
                'emoji' => null,
                'activity' => null,
                'timerMode' => null,
                'timerPhase' => null,
                'timerEndsAt' => null,
                'timerStartedAt' => null,
            ]).
            '})'
    );

    $page->assertMissing('[data-testid="study-room-control-panel"]');

    $isStillHighlighted = $page->script(
        "document.querySelector('[data-testid=\"seat-1-S01\"]').className.includes('amber')"
    );

    expect($isStillHighlighted)->toBeFalse();
});

it('keeps your own focus total intact when a realtime delta broadcasts for someone else', function () {
    // Regression test: BroadcastStudyRoomChange builds its payload with a
    // null viewer (one broadcast fans out to everyone), so
    // totals.yourFocusSecondsToday in every delta is always 0. applyDelta()
    // used to replace the whole totals object wholesale, so any seat
    // join/leave broadcast — even one for a completely different seat —
    // stomped your real "今天專注了" total with 0 until the next refresh().
    $schedule = createScheduleWithCourse();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');
    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    StudyRoomSession::factory()->create([
        'student_schedule_id' => $schedule->id,
        'focus_seconds' => 25 * 60,
        'started_at' => now()->subMinutes(30),
        'ended_at' => now()->subMinutes(5),
    ]);

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '認真讀書中')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->wait(1);

    $page->assertSeeIn('[data-testid="study-room-personal-info"]', '今天專注了 25 分');

    // Same payload shape a broadcast for an unrelated seat leaving sends.
    $page->script(
        'window.__studyRoomTest.socket.applyDelta('.
            '{openFloors: 1, totals: {occupantCount: 0, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "v2", seat: '.
            json_encode([
                'code' => '1-S02',
                'kind' => 'solo',
                'groupCode' => null,
                'seatNumber' => 2,
                'label' => '1-S02',
                'isOccupied' => false,
                'isYou' => false,
                'nickname' => null,
                'emoji' => null,
                'activity' => null,
                'timerMode' => null,
                'timerPhase' => null,
                'timerEndsAt' => null,
                'timerStartedAt' => null,
            ]).
            '})'
    );

    $page->assertSeeIn('[data-testid="study-room-personal-info"]', '今天專注了 25 分');
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
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-wall"] [data-testid="study-room-garden"]')
        ->assertVisible('[data-testid="study-room-wall"] [data-testid="study-room-sky-canvas"]')
        ->assertVisible('[data-testid="study-room-floor-1"] [data-testid="study-room-windows"]');

    $component = 'window.__studyRoomTest.sky';

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

    // The clock hanging beside the window reads Taipei time on its hands,
    // not the viewer's own zone. Stopping the tick first pins the clock, so
    // the hands can't move between setting the time and reading the DOM.
    $page->script($component.'.stopClock(); '.$component.'.clockNow = '.
        CarbonImmutable::parse('2026-06-21T15:20:30+08:00')->getTimestampMs());
    $page->wait(1);

    $hand = static fn (string $name): string => 'document.querySelector(\'[data-testid="study-room-clock-'.
        $name.'-hand"]\').style.transform';

    // 15:20:30 in Taipei: the minute hand half past the 4, the hour hand a
    // third of the way from 3 to 4.
    expect($page->script($hand('minute')))->toBe('translateX(-50%) rotate(123deg)')
        ->and($page->script($hand('hour')))->toBe('translateX(-50%) rotate(100.25deg)');

    expect($page->script($component.'.clockTimeLabel()'))->toBe('15:20');
});

it('pauses and resumes a running timer, freezing the countdown while paused', function () {
    $schedule = createScheduleWithCourse();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');
    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '暫停一下')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->wait(1);

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]')
        ->click('[data-testid="seat-1-S01"]')
        ->wait(1)
        ->click('[data-testid="study-room-start-timer"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '專注中')
        ->assertVisible('[data-testid="study-room-pause-timer"]')
        ->assertMissing('[data-testid="study-room-resume-timer"]');

    $page->click('[data-testid="study-room-pause-timer"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '已暫停')
        ->assertVisible('[data-testid="study-room-resume-timer"]')
        ->assertMissing('[data-testid="study-room-pause-timer"]');

    // The pause is recorded server-side, and the elapsed segment became a session.
    $seat = StudyRoomSeat::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($seat->paused_at)->not->toBeNull()
        ->and(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->count())->toBe(1);

    // Frozen: the countdown doesn't move while the clock keeps running.
    $countdown = static fn (): string => $page->script(
        'document.querySelector(\'[data-testid="study-room-your-countdown"]\').textContent.trim()'
    );

    $frozen = $countdown();
    $page->wait(2);
    expect($countdown())->toBe($frozen);

    $page->click('[data-testid="study-room-resume-timer"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '專注中')
        ->assertVisible('[data-testid="study-room-pause-timer"]')
        ->assertMissing('[data-testid="study-room-resume-timer"]');

    expect($seat->refresh()->paused_at)->toBeNull();

    // ...and it ticks again once resumed.
    $running = $countdown();
    $page->wait(2);
    expect($countdown())->not->toBe($running);
});
