<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Str;

// Keyboard and screen-reader support for 自習室: the live announcer, the
// arrow-key seat grid, the list view, focus management around the control
// panel and dialogs, and the page-scoped accesskeys. See AGENTS.md "自習室".

/**
 * Remembers a fresh schedule (with a study-room profile, so the nickname
 * prompt is skipped) and opens the room with its seats rendered.
 */
$enterStudyRoom = function (): mixed {
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Accessible Study Room Schedule',
    ]);

    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
    ]);

    StudyRoomProfile::factory()->create([
        'student_schedule_id' => $schedule->id,
        'nickname' => '鍵盤同學',
    ]);

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');
    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="seat-1-S01"]\') !== null');

    dismissCookieConsentBanner($page);

    return $page;
};

/**
 * Seats another (fictional) student in the given seat, directly in the
 * database, so the page's copy of the room is stale until it refreshes.
 */
$occupySeatBehindThePagesBack = function (string $code, string $nickname = '浣熊'): void {
    $other = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Someone else']);

    StudyRoomProfile::factory()->create([
        'student_schedule_id' => $other->id,
        'nickname' => $nickname,
    ]);

    StudyRoomSeat::query()->where('code', $code)->update([
        'student_schedule_id' => $other->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);
};

it('speaks a failed seat claim through the assertive live region', function () use ($enterStudyRoom, $occupySeatBehindThePagesBack) {
    $page = $enterStudyRoom();

    $page->assertPresent('[data-testid="study-room-announcer-polite"][role="status"]')
        ->assertPresent('[data-testid="study-room-announcer-assertive"][role="alert"]');

    $occupySeatBehindThePagesBack('1-S02');

    $page->click('[data-testid="seat-1-S02"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-announcer-assertive"]\').textContent.trim() !== ""');

    $spoken = $page->script("document.querySelector('[data-testid=\"study-room-announcer-assertive\"]').textContent.trim()");
    $shown = $page->script("document.querySelector('[data-testid=\"study-room-error-toast\"]').textContent.trim()");

    expect($spoken)->not->toBe('')
        ->and($shown)->toContain($spoken);
});

$activeTestId = 'document.activeElement?.dataset.testid ?? null';

it('keeps occupied seats focusable and names who sits there', function () use ($enterStudyRoom, $occupySeatBehindThePagesBack, $activeTestId) {
    $page = $enterStudyRoom();

    $occupySeatBehindThePagesBack('1-S02', '浣熊');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="seat-1-S02"]\')?.getAttribute("aria-disabled") === "true"');

    $page->assertAttributeContains('[data-testid="seat-1-S02"]', 'aria-label', '浣熊 正在使用')
        ->assertAttributeMissing('[data-testid="seat-1-S02"]', 'disabled');

    $page->script("document.querySelector('[data-testid=\"seat-1-S02\"]').focus()");

    expect($page->script($activeTestId))->toBe('seat-1-S02');
});

it('gives each floor one Tab stop and moves between seats with the arrow keys', function () use ($enterStudyRoom, $activeTestId) {
    $page = $enterStudyRoom();

    $tabStops = $page->script(
        "document.querySelectorAll('[data-testid=\"study-room-floor-1-seats\"] [data-seat-code][tabindex=\"0\"]').length"
    );

    expect($tabStops)->toBe(1);

    $page->keys('[data-testid="seat-1-S01"]', 'ArrowRight');
    expect($page->script($activeTestId))->toBe('seat-1-S02');

    $page->keys('[data-testid="seat-1-S02"]', 'ArrowLeft');
    expect($page->script($activeTestId))->toBe('seat-1-S01');

    $page->keys('[data-testid="seat-1-S01"]', 'End');
    $last = $page->script(
        "[...document.querySelectorAll('[data-testid=\"study-room-floor-1-seats\"] [data-seat-code]')].at(-1).dataset.testid"
    );
    expect($page->script($activeTestId))->toBe($last);

    $page->keys('[data-testid="'.$last.'"]', 'Home');
    expect($page->script($activeTestId))->toBe('seat-1-S01');

    // Down lands on a seat that is really further down the floor.
    $page->keys('[data-testid="seat-1-S01"]', 'ArrowDown');
    $movedDown = $page->script(
        "document.activeElement.getBoundingClientRect().top > document.querySelector('[data-testid=\"seat-1-S01\"]').getBoundingClientRect().bottom - 1"
    );
    expect($movedDown)->toBeTrue();

    // The Tab stop follows focus, so there is still exactly one.
    $current = $page->script($activeTestId);
    expect($page->script("document.querySelector('[data-testid=\"{$current}\"]').tabIndex"))->toBe(0)
        ->and($page->script("document.querySelector('[data-testid=\"seat-1-S01\"]').tabIndex"))->toBe(-1);

    // One Tab leaves the floor instead of walking every seat.
    $page->keys('[data-testid="'.$current.'"]', 'Tab');
    $stillOnFloor = $page->script(
        "document.querySelector('[data-testid=\"study-room-floor-1-seats\"]').contains(document.activeElement)"
    );
    expect($stillOnFloor)->toBeFalse();
});

it('opens a table chair popover on keyboard focus', function () use ($enterStudyRoom, $occupySeatBehindThePagesBack) {
    $page = $enterStudyRoom();

    $occupySeatBehindThePagesBack('1-T1-1', '浣熊');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="seat-1-T1-1"]\')?.getAttribute("aria-disabled") === "true"');

    $page->assertMissing('[data-testid="seat-1-T1-1-popover"]');
    $page->script("document.querySelector('[data-testid=\"seat-1-T1-1\"]').focus()");
    $page->assertVisible('[data-testid="seat-1-T1-1-popover"]')
        ->assertAttribute('[data-testid="seat-1-T1-1-popover"]', 'aria-hidden', 'true');
});

$politeText = "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.trim()";
$assertiveText = "document.querySelector('[data-testid=\"study-room-announcer-assertive\"]').textContent.trim()";

it('moves focus into the control panel on taking a seat and back to the seat on leaving', function () use ($enterStudyRoom, $activeTestId, $politeText) {
    $page = $enterStudyRoom();

    $page->keys('[data-testid="seat-1-S01"]', 'Enter');

    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-verb-exam_prep'");

    expect($page->script($politeText))->toContain('已入座');

    $page->click('[data-testid="study-room-leave-seat"]');

    waitUntil($page, "document.activeElement?.dataset.testid === 'seat-1-S01'");

    expect($page->script($activeTestId))->toBe('seat-1-S01')
        ->and($page->script($politeText))->toBe('已離開座位');
});

it('keeps focus on the matching control as the panel swaps buttons', function () use ($enterStudyRoom, $activeTestId) {
    $page = $enterStudyRoom();

    $page->keys('[data-testid="seat-1-S01"]', 'Enter');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-start-timer"]\') !== null');

    $page->keys('[data-testid="study-room-start-timer"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-pause-timer'");

    $page->keys('[data-testid="study-room-pause-timer"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-resume-timer'");

    $page->keys('[data-testid="study-room-resume-timer"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-pause-timer'");

    $page->keys('[data-testid="study-room-banner-minimize"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-banner-expand'");

    $page->keys('[data-testid="study-room-banner-expand"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-banner-minimize'");

    $page->keys('[data-testid="study-room-stop-timer"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-start-timer'");

    expect($page->script($activeTestId))->toBe('study-room-start-timer');
});

it('traps focus in the study room dialogs and returns it to the opener', function () use ($enterStudyRoom, $activeTestId) {
    $page = $enterStudyRoom();

    $page->keys('[data-testid="study-room-personal-info-stats"]', 'Enter');

    waitUntil($page, "document.querySelector('[data-testid=\"study-room-stats-modal\"]').contains(document.activeElement)");

    $labelled = $page->script(
        "(() => { const dialog = document.querySelector('[data-testid=\"study-room-stats-modal\"]'); return document.getElementById(dialog.getAttribute('aria-labelledby'))?.textContent.trim(); })()"
    );
    expect($labelled)->toBe('專注紀錄與統計');

    // Tabbing past the last control wraps to the first instead of leaving.
    $page->script(<<<'JS'
        (() => {
            const buttons = [...document.querySelectorAll('[data-testid="study-room-stats-modal"] button')]
                .filter(button => button.getClientRects().length > 0)
            buttons.at(-1).focus()
        })()
        JS);
    $last = $page->script($activeTestId);
    $page->keys('[data-testid="'.$last.'"]', 'Tab');

    $stillInside = $page->script("document.querySelector('[data-testid=\"study-room-stats-modal\"]').contains(document.activeElement)");
    expect($stillInside)->toBeTrue();

    $page->keys('[data-testid="study-room-stats-modal"]', 'Escape');

    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-personal-info-stats'");
    expect($page->script($activeTestId))->toBe('study-room-personal-info-stats');
});

it('announces a seat released by the server and returns focus to it', function () use ($enterStudyRoom, $assertiveText) {
    $page = $enterStudyRoom();

    $page->keys('[data-testid="seat-1-S01"]', 'Enter');

    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-verb-exam_prep'");

    $page->script(
        'window.__studyRoomTest.socket.applyDelta('.
            '{openFloors: 1, totals: {occupantCount: 0, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "released", seat: '.
            json_encode([
                'code' => '1-S01',
                'kind' => 'solo',
                'groupCode' => null,
                'seatNumber' => 1,
                'label' => '1F 單人座 01',
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

    waitUntil($page, "document.activeElement?.dataset.testid === 'seat-1-S01'");

    expect($page->script($assertiveText))->toContain('已被釋放');
});

it('focuses the exit button in focus mode and returns to the 全螢幕 button afterwards', function () use ($enterStudyRoom) {
    $page = $enterStudyRoom();

    $page->keys('[data-testid="seat-1-S01"]', 'Enter');
    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-start-timer"]\') !== null');
    $page->keys('[data-testid="study-room-start-timer"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-pause-timer'");

    $page->keys('[data-testid="study-room-focus-mode-open"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-focus-mode-close'");

    $page->keys('[data-testid="study-room-focus-mode-close"]', 'Escape');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-focus-mode-open'");

    expect($page->script('document.activeElement.dataset.testid'))->toBe('study-room-focus-mode-open');
});
