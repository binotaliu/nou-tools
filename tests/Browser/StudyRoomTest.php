<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Str;

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
        ->waitForEvent('load');

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

    $component = 'document.querySelector(\'[data-testid="study-room-page"]\')._x_dataStack[0]';

    $page->script($component.'.refresh()');
    $page->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '這一輪完成了')
        ->assertVisible('[data-testid="study-room-start-break"]')
        ->click('[data-testid="study-room-start-break"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '休息一下')
        ->assertVisible('[data-testid="study-room-next-round"]')
        ->assertSeeIn('[data-testid="study-room-next-round"]', '第 2 輪');

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
        ->assertSeeIn('[data-testid="study-room-start-break"]', '開始長休息')
        ->click('[data-testid="study-room-start-break"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-timer-phase"]', '長休息');

    $seat->refresh();
    expect((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(20);
});
