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

it('announces a seat released by the server and offers 快速入座 again', function () use ($enterStudyRoom, $assertiveText) {
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

    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-quick-seat'");

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

it('takes the first free seat with 快速入座', function () use ($enterStudyRoom, $occupySeatBehindThePagesBack, $politeText) {
    $page = $enterStudyRoom();

    $occupySeatBehindThePagesBack('1-S01');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="seat-1-S01"]\')?.getAttribute("aria-disabled") === "true"');

    $page->click('[data-testid="study-room-quick-seat"]');

    waitUntil($page, 'window.__studyRoomTest.socket.heldSeatCode === "1-S02"');

    $page->assertMissing('[data-testid="study-room-quick-seat"]');
    expect($page->script($politeText))->toContain('已入座 1F 單人座 02');
});

it('shows the room as a table in the list view and remembers the choice', function () use ($enterStudyRoom, $occupySeatBehindThePagesBack) {
    $page = $enterStudyRoom();

    $occupySeatBehindThePagesBack('1-S02', '浣熊');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-view-list"]\') !== null');

    $page->click('[data-testid="study-room-view-list"]')
        ->assertVisible('[data-testid="study-room-list-floor-1"]')
        ->assertMissing('[data-testid="study-room-floor-1"]')
        ->assertAttribute('[data-testid="study-room-view-list"]', 'aria-pressed', 'true')
        ->assertSeeIn('[data-testid="study-room-list-floor-1"] caption', '一樓')
        ->assertSeeIn('[data-testid="study-room-list-row-1-S02"]', '浣熊')
        ->assertMissing('[data-testid="study-room-list-take-1-S02"]');

    $page->click('[data-testid="study-room-seat-list-filter-occupied"]');

    $rows = $page->script(
        "document.querySelectorAll('[data-testid^=\"study-room-list-row-\"]').length"
    );
    expect($rows)->toBe(1);

    $page->click('[data-testid="study-room-seat-list-filter-all"]')
        ->click('[data-testid="study-room-list-take-1-S03"]');

    waitUntil($page, 'window.__studyRoomTest.socket.heldSeatCode === "1-S03"');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-list-floor-1"]\') !== null');

    $page->assertMissing('[data-testid="study-room-floor-1"]')
        ->click('[data-testid="study-room-leave-seat"]');

    // Leaving from the list returns focus to the seat's row.
    waitUntil($page, "document.activeElement?.dataset.seatCode === '1-S03'");
});

/**
 * Takes seat 1-S01 and starts a default pomodoro.
 */
$sitAndStartTimer = function (mixed $page): mixed {
    $page->keys('[data-testid="seat-1-S01"]', 'Enter');
    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-start-timer"]\') !== null');
    $page->keys('[data-testid="study-room-start-timer"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-pause-timer'");

    return $page;
};

it('announces your own timer changes', function () use ($enterStudyRoom, $sitAndStartTimer, $politeText) {
    $page = $sitAndStartTimer($enterStudyRoom());

    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.includes('開始專注')");

    $page->keys('[data-testid="study-room-pause-timer"]', 'Enter');
    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.trim() === '已暫停'");

    $page->keys('[data-testid="study-room-resume-timer"]', 'Enter');
    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.trim() === '已繼續'");

    $page->keys('[data-testid="study-room-stop-timer"]', 'Enter');
    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.trim() === '已結束計時'");

    expect($page->script($politeText))->toBe('已結束計時');
});

it('announces the end of a focus round and, when asked, the time left', function () use ($enterStudyRoom, $sitAndStartTimer, $politeText) {
    $page = $sitAndStartTimer($enterStudyRoom());

    $page->click('[data-testid="study-room-voice-settings-toggle"]')
        ->select('[data-testid="study-room-voice-interval"]', '5');

    expect($page->script('localStorage.getItem("nou:study-room:announce-interval:v1")'))->toBe('5');

    // Move the end just past a five-minute mark: the next minute boundary
    // lands on 5 and is spoken.
    $page->script('window.__studyRoomTest.socket.mySeat().timerEndsAt = new Date(Date.now() + 5 * 60000 + 1500).toISOString()');
    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.trim() === '剩 5 分鐘'");

    $page->script('window.__studyRoomTest.socket.mySeat().timerEndsAt = new Date(Date.now() + 1500).toISOString()');
    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.includes('專注時間到')");

    expect($page->script($politeText))->toContain('專注時間到');
});

$deltaFor = fn (string $code, int $seatNumber, ?string $nickname): string => 'window.__studyRoomTest.socket.applyDelta('.
    '{openFloors: 1, totals: {occupantCount: 1, siteFocusSecondsToday: 0, yourFocusSecondsToday: 0}, version: "'.Str::random(6).'", seat: '.
    json_encode([
        'code' => $code,
        'kind' => 'solo',
        'groupCode' => null,
        'seatNumber' => $seatNumber,
        'label' => '1F 單人座 '.str_pad((string) $seatNumber, 2, '0', STR_PAD_LEFT),
        'isOccupied' => $nickname !== null,
        'isYou' => false,
        'nickname' => $nickname,
        'emoji' => $nickname ? '🦝' : null,
        'activity' => null,
        'timerMode' => null,
        'timerPhase' => null,
        'timerEndsAt' => null,
        'timerStartedAt' => null,
    ]).
    '})';

it('announces neighbours coming and going only when asked to', function () use ($enterStudyRoom, $politeText, $deltaFor) {
    $page = $enterStudyRoom();

    $page->keys('[data-testid="seat-1-S01"]', 'Enter');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-verb-exam_prep'");

    // Off by default: a neighbour sitting down stays silent.
    $page->script($deltaFor('1-S02', 2, '浣熊'));
    $page->script('new Promise(resolve => setTimeout(resolve, 3500))');
    expect($page->script($politeText))->not->toContain('浣熊');

    $page->click('[data-testid="study-room-voice-settings-toggle"]')
        ->select('[data-testid="study-room-voice-room"]', 'neighbors');

    $page->script($deltaFor('1-S02', 2, null));
    // Not a neighbour of 1-S01, so left out of the sentence.
    $page->script($deltaFor('1-S05', 5, '夜貓'));

    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.includes('離開了')");

    expect($page->script($politeText))->toBe('隔壁的 浣熊 離開了');
});

$accessKeyMap = "JSON.stringify(Object.fromEntries([...document.querySelectorAll('[accesskey]')].map(e => [e.accessKey, e.getAttribute('href') ?? e.dataset.testid])))";

it('puts each study room accesskey on exactly one element, seated or not', function () use ($enterStudyRoom, $sitAndStartTimer, $accessKeyMap) {
    $page = $enterStudyRoom();

    $keys = json_decode($page->script($accessKeyMap), true);
    ksort($keys);

    expect($keys)->toBe([
        '0' => '/accessibility',
        '1' => '#main-content',
        '2' => '/schedules/my',
        '3' => 'theme-switcher-toggle',
        '4' => 'study-room-quick-seat',
        '6' => 'study-room-accesskey-seats',
    ])->and($page->script("document.querySelectorAll('[accesskey]').length"))->toBe(6);

    $sitAndStartTimer($page);

    $keys = json_decode($page->script($accessKeyMap), true);
    ksort($keys);

    expect($keys)->toBe([
        '0' => '/accessibility',
        '1' => '#main-content',
        '2' => '/schedules/my',
        '3' => 'theme-switcher-toggle',
        '4' => 'study-room-accesskey-panel',
        '5' => 'study-room-accesskey-timer',
        '6' => 'study-room-accesskey-seats',
        '7' => 'study-room-accesskey-status',
        '8' => 'study-room-accesskey-focus-mode',
    ])->and($page->script("document.querySelectorAll('[accesskey]').length"))->toBe(9);
});

it('toggles the timer, reads the status and jumps to your seat from the accesskeys', function () use ($enterStudyRoom, $sitAndStartTimer, $politeText, $activeTestId) {
    $page = $sitAndStartTimer($enterStudyRoom());

    $page->script("document.querySelector('[data-testid=\"study-room-accesskey-timer\"]').click()");
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-resume-timer'");

    $page->script("document.querySelector('[data-testid=\"study-room-accesskey-status\"]').click()");
    waitUntil($page, "document.querySelector('[data-testid=\"study-room-announcer-polite\"]').textContent.includes('1F 單人座 01')");
    expect($page->script($politeText))->toContain('已暫停');

    $page->script("document.querySelector('[data-testid=\"study-room-accesskey-seats\"]').click()");
    waitUntil($page, "document.activeElement?.dataset.testid === 'seat-1-S01'");

    // A real accesskey focuses its element before clicking it.
    $page->script("(() => { const key = document.querySelector('[data-testid=\"study-room-accesskey-focus-mode\"]'); key.focus(); key.click() })()");
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-focus-mode-close'");

    // Leaving focus mode doesn't return to the hidden accesskey button.
    $page->keys('[data-testid="study-room-focus-mode-close"]', 'Escape');
    waitUntil($page, "document.activeElement?.dataset.testid === 'study-room-control-panel-heading'");

    expect($page->script($activeTestId))->toBe('study-room-control-panel-heading');
});

it('labels the nameplate, the change-activity button and the board heading', function () use ($enterStudyRoom, $sitAndStartTimer) {
    $page = $sitAndStartTimer($enterStudyRoom());

    $page->assertAttribute('[data-testid="study-room-change-activity-open"]', 'aria-label', '變更活動')
        ->assertSeeIn('[data-testid="study-room-announcement"] h3', '公告板')
        ->assertSourceInHas('[data-testid="study-room-personal-info"]', '你的暱稱：');

    // The radio labels show a ring when their sr-only input has keyboard focus.
    $page->script("document.querySelector('[data-testid=\"study-room-change-activity-open\"]').click()");
    $page->keys('[data-testid="study-room-change-verb-reading"]', 'Space');

    $ring = $page->script(
        "getComputedStyle(document.querySelector('[data-testid=\"study-room-change-verb-reading\"]').closest('label')).boxShadow"
    );
    expect($ring)->not->toBe('none');
});

it('exposes the seats as a 座位表 landmark with the floors as headings under it', function () use ($enterStudyRoom) {
    $page = $enterStudyRoom();

    $landmark = $page->script(<<<'JS'
        (() => {
            const region = document.querySelector('section[data-testid="study-room-seats"]')
            return document.getElementById(region.getAttribute('aria-labelledby'))?.textContent.trim()
        })()
        JS);

    expect($landmark)->toBe('座位表')
        ->and($page->script("document.querySelector('[data-testid=\"study-room-seats\"]').contains(document.querySelector('[data-testid=\"study-room-toolbar\"]'))"))->toBeTrue()
        ->and($page->script("document.getElementById('study-room-floor-heading-1').tagName"))->toBe('H4');

    $page->assertAttribute('[data-testid="study-room-toolbar"]', 'aria-label', '座位工具');
});
