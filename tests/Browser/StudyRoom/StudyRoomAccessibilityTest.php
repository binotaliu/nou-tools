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
