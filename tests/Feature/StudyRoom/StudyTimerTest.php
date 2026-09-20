<?php

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerPhase;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\StudyRoom\Actions\ReleaseIdleSeats;

function studyTimerCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

function seatedStudent(): array
{
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create(['floor' => 1]);

    return [$schedule, $seat];
}

it('sets timer_ends_at to 25 minutes out for a pomodoro', function () {
    [$schedule, $seat] = seatedStudent();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ]);

    $response->assertOk();

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Focus)
        ->and((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(25);
});

it('records exactly 600 focus seconds when stopping a pomodoro 10 minutes in', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(10)->minutes();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'));

    $response->assertOk();

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->focus_seconds)->toBe(600)
        ->and($session->was_completed)->toBeFalse();

    $seat->refresh();
    expect($seat->timer_mode)->toBeNull()
        ->and($seat->timer_phase)->toBeNull();

    Date::setTestNow();
});

it('rejects custom minutes outside the configured bounds', function () {
    [$schedule] = seatedStudent();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => (int) config('study-room.timer.custom.max_minutes') + 1,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors('minutes');
});

it('rejects a course that is not in the caller\'s own schedule', function () {
    [$schedule] = seatedStudent();

    $otherSchedule = StudentSchedule::factory()->create();
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $courseClass = CourseClass::factory()->for($course)->create();
    StudentScheduleItem::query()->create([
        'student_schedule_id' => $otherSchedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => $course->id,
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors('subjectCourseId');
});

it('records the fixed 其他 label when no course is selected', function () {
    [$schedule, $seat] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $seat->refresh();
    expect($seat->subject_label)->toBe('其他')
        ->and($seat->subject_course_id)->toBeNull();
});

it('starts a timer for a course that is in the caller\'s schedule for the current term', function () {
    [$schedule, $seat] = seatedStudent();

    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $courseClass = CourseClass::factory()->for($course)->create();
    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => $course->id,
        ]);

    $response->assertOk();

    $seat->refresh();
    expect($seat->subject_course_id)->toBe($course->id)
        ->and($seat->subject_label)->toBeNull();
});

it('records no session when stopping a break-phase timer', function () {
    [$schedule, $seat] = seatedStudent();

    $seat->update([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Break,
        'timer_started_at' => now(),
        'timer_ends_at' => now()->addMinutes(5),
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    expect(StudyRoomSession::query()->count())->toBe(0);
});

it('records the focus session when leaving a seat mid-timer', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(5)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.seat.leave'))
        ->assertOk();

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->focus_seconds)->toBe(300);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull();

    Date::setTestNow();
});

it('respects the Asia/Taipei day boundary for daily totals', function () {
    [$schedule, $seat] = seatedStudent();

    // 2026-01-02 23:50 Asia/Taipei == 2026-01-02 15:50 UTC — still "today" (Jan 2) locally.
    Date::setTestNow(Date::parse('2026-01-02 15:50:00', 'UTC'));

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    Date::setTestNow(Date::parse('2026-01-02 15:55:00', 'UTC'));

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $beforeMidnightState = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->getJson(route('study-room.state'));

    expect($beforeMidnightState->json('totals.yourFocusSecondsToday'))->toBe(300);

    // Cross local midnight: 2026-01-02 16:05 UTC == 2026-01-03 00:05 Asia/Taipei.
    Date::setTestNow(Date::parse('2026-01-02 16:05:00', 'UTC'));

    $seat->refresh();

    $afterMidnightState = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->getJson(route('study-room.state'));

    expect($afterMidnightState->json('totals.yourFocusSecondsToday'))->toBe(0);
    expect($afterMidnightState->json('totals.siteFocusSecondsToday'))->toBe(0);

    Date::setTestNow();
});

it('saves the sent pomodoro cycle as the student\'s preference and starts round 1 of it', function () {
    [$schedule, $seat] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
            'focusMinutes' => 50,
            'shortBreakMinutes' => 10,
            'longBreakMinutes' => 20,
            'roundsPerCycle' => 2,
        ])->assertOk()
        ->assertJsonPath('state.floors.0.soloSeats.0.timerRound', 1)
        ->assertJsonPath('state.floors.0.soloSeats.0.roundsPerCycle', 2);

    $seat->refresh();
    expect($seat->timer_round)->toBe(1)
        ->and((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(50);

    $profile = StudyRoomProfile::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($profile->pomodoro_focus_minutes)->toBe(50)
        ->and($profile->pomodoro_short_break_minutes)->toBe(10)
        ->and($profile->pomodoro_long_break_minutes)->toBe(20)
        ->and($profile->pomodoro_rounds_per_cycle)->toBe(2);

    // The saved cycle is what the page hands back next time.
    $page = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->get(route('study-room.show'));

    $page->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('profile.pomodoroCycle.focusMinutes', 50)
    );
});

it('rejects a pomodoro cycle outside the configured bounds', function () {
    [$schedule] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
            'focusMinutes' => 121,
            'shortBreakMinutes' => 0,
            'longBreakMinutes' => 30,
            'roundsPerCycle' => 13,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['focusMinutes', 'shortBreakMinutes', 'roundsPerCycle']);
});

it('runs a short break after a mid-cycle round and the long break after the last round', function () {
    [$schedule, $seat] = seatedStudent();

    StudyRoomProfile::query()->where('student_schedule_id', $schedule->id)->update([
        'pomodoro_focus_minutes' => 25,
        'pomodoro_short_break_minutes' => 5,
        'pomodoro_long_break_minutes' => 30,
        'pomodoro_rounds_per_cycle' => 2,
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    // Round 1 finishes → short break.
    $this->travel(25)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.break'))
        ->assertOk();

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Break)
        ->and($seat->timer_round)->toBe(1)
        ->and((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(5);

    // Skipping ahead mid-break is allowed and starts round 2.
    $this->travel(2)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.next'))
        ->assertOk();

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Focus)
        ->and($seat->timer_round)->toBe(2)
        ->and((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(25);

    // Round 2 is the last of the cycle → long break.
    $this->travel(25)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.break'))
        ->assertOk();

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Break)
        ->and((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(30);

    // Both focus rounds were recorded as completed sessions.
    expect(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->where('was_completed', true)->count())->toBe(2);
});

it('lets a pomodoro round be skipped into its break, recording the elapsed focus as unfinished', function () {
    [$schedule, $seat] = seatedStudent();

    StudyRoomProfile::query()->where('student_schedule_id', $schedule->id)->update([
        'pomodoro_focus_minutes' => 25,
        'pomodoro_short_break_minutes' => 5,
        'pomodoro_long_break_minutes' => 30,
        'pomodoro_rounds_per_cycle' => 2,
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    // Ten minutes into a 25-minute round.
    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.break'))
        ->assertOk();

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Break)
        ->and($seat->timer_round)->toBe(1)
        ->and((int) $seat->timer_started_at->diffInMinutes($seat->timer_ends_at))->toBe(5);

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->was_completed)->toBeFalse()
        ->and($session->focus_seconds)->toBe(600)
        ->and($session->overtime_seconds)->toBe(0);
});

it('refuses to skip a paused pomodoro round into its break', function () {
    [$schedule, $seat] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.break'))
        ->assertStatus(422);

    expect($seat->refresh()->timer_phase)->toBe(StudyTimerPhase::Focus);
});

it('refuses to start the next round unless the seat is on a pomodoro break', function () {
    [$schedule, $seat] = seatedStudent();

    // No timer at all.
    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.next'))
        ->assertStatus(422);

    // Mid-focus.
    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.next'))
        ->assertStatus(422);

    $seat->refresh();
    expect($seat->timer_round)->toBe(1);
});

it('records overtime past the planned end when stopping a custom timer late', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 10,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    // 10 planned minutes plus 3 extra minutes run past timer_ends_at.
    $this->travel(13)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->focus_seconds)->toBe(13 * 60)
        ->and($session->overtime_seconds)->toBe(3 * 60)
        ->and($session->was_completed)->toBeTrue();

    Date::setTestNow();
});

it('starts a count-up timer with no planned end and no round', function () {
    [$schedule, $seat] = seatedStudent();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'count_up',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ]);

    $response->assertOk()
        ->assertJsonPath('state.floors.0.soloSeats.0.timerRound', null)
        ->assertJsonPath('state.floors.0.soloSeats.0.timerEndsAt', null);

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Focus)
        ->and($seat->timer_round)->toBeNull()
        ->and($seat->timer_ends_at)->toBeNull()
        ->and($seat->timer_started_at)->not->toBeNull();
});

it('records the elapsed time with no overtime when stopping a count-up timer', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'count_up',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(7)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->focus_seconds)->toBe(7 * 60)
        ->and($session->overtime_seconds)->toBe(0)
        ->and($session->was_completed)->toBeTrue();

    Date::setTestNow();
});

it('refuses to start a break on a count-up timer, which has no planned end', function () {
    [$schedule, $seat] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'count_up',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(30)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.break'))
        ->assertStatus(422);
});

it('splits a session at the point activity changes mid-focus, crediting the old activity for elapsed time', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $originalStartedAt = $seat->refresh()->timer_started_at;
    $originalEndsAt = $seat->timer_ends_at;

    $this->travel(10)->minutes();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => null,
        ]);

    $response->assertOk();

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->activity_verb)->toBe(StudyActivityVerb::Review)
        ->and($session->focus_seconds)->toBe(600)
        ->and($session->was_completed)->toBeFalse();

    $seat->refresh();
    expect($seat->activity_verb)->toBe(StudyActivityVerb::Homework)
        ->and($seat->timer_phase)->toBe(StudyTimerPhase::Focus)
        // The round's own countdown/progress-bar anchor is untouched by an
        // activity change — only the new segment's own start moves.
        ->and($seat->timer_started_at->equalTo($originalStartedAt))->toBeTrue()
        ->and($seat->timer_ends_at->equalTo($originalEndsAt))->toBeTrue()
        ->and((int) $seat->activity_started_at->diffInMinutes($seat->timer_ends_at))->toBe(15);

    Date::setTestNow();
});

it('records a second session for the new activity, correctly measuring only its own remaining plan', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 20,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(8)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => null,
        ])->assertOk();

    // Remaining plan for the new segment is 12 minutes; run 15 (3 overtime).
    $this->travel(15)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $sessions = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2);

    $newSession = $sessions->last();
    expect($newSession->activity_verb)->toBe(StudyActivityVerb::Homework)
        ->and($newSession->focus_seconds)->toBe(15 * 60)
        ->and($newSession->overtime_seconds)->toBe(3 * 60)
        ->and($newSession->was_completed)->toBeTrue();

    Date::setTestNow();
});

it('refuses to change activity when no timer is running', function () {
    [$schedule] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => null,
        ])
        ->assertStatus(422);
});

it('refuses to change activity while on a break', function () {
    [$schedule, $seat] = seatedStudent();

    $seat->update([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Break,
        'timer_started_at' => now(),
        'timer_ends_at' => now()->addMinutes(5),
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => null,
        ])
        ->assertStatus(422);

    $seat->refresh();
    expect($seat->activity_verb)->toBe(StudyActivityVerb::Review);
});

it('rejects a course that is not in the caller\'s own schedule when changing activity', function () {
    [$schedule, $seat] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $otherSchedule = StudentSchedule::factory()->create();
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $courseClass = CourseClass::factory()->for($course)->create();
    StudentScheduleItem::query()->create([
        'student_schedule_id' => $otherSchedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => $course->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('subjectCourseId');

    $seat->refresh();
    expect($seat->activity_verb)->toBe(StudyActivityVerb::Review);
});

it('leaves a custom timer without a round and stopping clears it', function () {
    [$schedule, $seat] = seatedStudent();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 40,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk()
        ->assertJsonPath('state.floors.0.soloSeats.0.timerRound', null);

    $seat->refresh();
    expect($seat->timer_round)->toBeNull();
});

it('pauses a running timer, recording the elapsed segment while the progress bar anchors stay put', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $originalStartedAt = $seat->refresh()->timer_started_at;
    $originalEndsAt = $seat->timer_ends_at;

    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk()
        ->assertJsonPath('state.floors.0.soloSeats.0.pausedAt', now()->toIso8601String());

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->activity_verb)->toBe(StudyActivityVerb::Review)
        ->and($session->focus_seconds)->toBe(600)
        ->and($session->was_completed)->toBeFalse();

    $seat->refresh();
    expect($seat->paused_at->getTimestamp())->toBe(now()->getTimestamp())
        ->and($seat->timer_phase)->toBe(StudyTimerPhase::Focus)
        ->and($seat->timer_started_at->equalTo($originalStartedAt))->toBeTrue()
        ->and($seat->timer_ends_at->equalTo($originalEndsAt))->toBeTrue();

    Date::setTestNow();
});

it('resumes by shifting the timer forward by the pause and measuring only the remaining plan', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 20,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $originalStartedAt = $seat->refresh()->timer_started_at;
    $originalEndsAt = $seat->timer_ends_at;

    $this->travel(8)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->travel(30)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.resume'))
        ->assertOk()
        ->assertJsonPath('state.floors.0.soloSeats.0.pausedAt', null);

    $seat->refresh();
    expect($seat->paused_at)->toBeNull()
        ->and($seat->timer_started_at->equalTo($originalStartedAt->addMinutes(30)))->toBeTrue()
        ->and($seat->timer_ends_at->equalTo($originalEndsAt->addMinutes(30)))->toBeTrue()
        ->and($seat->activity_started_at->getTimestamp())->toBe(now()->getTimestamp());

    // 12 minutes of the plan remain; run 15 (3 overtime).
    $this->travel(15)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $sessions = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2)
        ->and($sessions->first()->focus_seconds)->toBe(8 * 60)
        ->and($sessions->last()->focus_seconds)->toBe(15 * 60)
        ->and($sessions->last()->overtime_seconds)->toBe(3 * 60)
        ->and($sessions->last()->was_completed)->toBeTrue();

    Date::setTestNow();
});

it('does not record the overtime twice when pausing past the planned end and carrying on', function () {
    [$schedule] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 20,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(25)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.resume'))
        ->assertOk();

    $this->travel(5)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $sessions = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2)
        ->and($sessions->first()->focus_seconds)->toBe(25 * 60)
        ->and($sessions->first()->overtime_seconds)->toBe(5 * 60)
        // The 5 minutes of overtime before the pause are already in the first
        // session; the segment after resuming is overtime in full, no more.
        ->and($sessions->last()->focus_seconds)->toBe(5 * 60)
        ->and($sessions->last()->overtime_seconds)->toBe(5 * 60)
        ->and($sessions->last()->was_completed)->toBeTrue();

    Date::setTestNow();
});

it('does not record the overtime twice when changing activity past the planned end', function () {
    [$schedule] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 20,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(25)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(5)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $sessions = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->orderBy('id')->get();
    expect($sessions->first()->overtime_seconds)->toBe(5 * 60)
        ->and($sessions->last()->focus_seconds)->toBe(5 * 60)
        ->and($sessions->last()->overtime_seconds)->toBe(5 * 60);

    Date::setTestNow();
});

it('keeps the progress bar continuous across repeated pauses', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $call = fn (string $route, array $body = []) => $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route($route), $body)
        ->assertOk();

    $progressAt = fn (CarbonInterface $at): float => ($at->getTimestamp() - $seat->refresh()->timer_started_at->getTimestamp())
        / ($seat->timer_ends_at->getTimestamp() - $seat->timer_started_at->getTimestamp());

    $call('study-room.timer.start', [
        'mode' => 'custom',
        'minutes' => 20,
        'verb' => StudyActivityVerb::Review->value,
        'subjectCourseId' => null,
    ]);

    $this->travel(5)->minutes();
    $call('study-room.timer.pause');
    $frozenAt = $progressAt($seat->refresh()->paused_at);

    $this->travel(10)->minutes();
    $call('study-room.timer.resume');
    expect($progressAt(now()))->toEqualWithDelta($frozenAt, 0.0001);

    $this->travel(5)->minutes();
    $call('study-room.timer.pause');
    $frozenAt = $progressAt($seat->refresh()->paused_at);

    $this->travel(20)->minutes();
    $call('study-room.timer.resume');
    expect($progressAt(now()))->toEqualWithDelta($frozenAt, 0.0001)
        ->and($frozenAt)->toEqualWithDelta(0.5, 0.0001);

    Date::setTestNow();
});

it('keeps a count-up timer open-ended when resuming', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'count_up',
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $originalStartedAt = $seat->refresh()->timer_started_at;

    $this->travel(5)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.resume'))
        ->assertOk();

    $seat->refresh();
    expect($seat->timer_ends_at)->toBeNull()
        ->and($seat->timer_started_at->equalTo($originalStartedAt->addMinutes(10)))->toBeTrue();

    Date::setTestNow();
});

it('does not record the pause as study time when stopping while paused', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'pomodoro',
            'minutes' => null,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->travel(20)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    expect(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole()->focus_seconds)->toBe(600);

    $seat->refresh();
    expect($seat->paused_at)->toBeNull()
        ->and($seat->timer_mode)->toBeNull();

    Date::setTestNow();
});

it('does not record the pause as study time when a paused seat is released for being idle', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 30,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->travel(2)->hours();

    expect(app(ReleaseIdleSeats::class)())->toBe(1);

    expect(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole()->focus_seconds)->toBe(600);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull()
        ->and($seat->paused_at)->toBeNull();

    Date::setTestNow();
});

it('lets the activity be changed while paused, applying it to the resumed segment without a new session', function () {
    [$schedule, $seat] = seatedStudent();

    Date::setTestNow(Date::now());

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 30,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])->assertOk();

    $this->travel(10)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'))
        ->assertOk();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->patchJson(route('study-room.timer.activity'), [
            'verb' => StudyActivityVerb::Homework->value,
            'subjectCourseId' => null,
        ])->assertOk();

    expect(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->count())->toBe(1);

    $this->travel(5)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.resume'))
        ->assertOk();

    $this->travel(20)->minutes();

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk();

    $sessions = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2)
        ->and($sessions->first()->activity_verb)->toBe(StudyActivityVerb::Review)
        ->and($sessions->last()->activity_verb)->toBe(StudyActivityVerb::Homework)
        ->and($sessions->last()->focus_seconds)->toBe(20 * 60)
        ->and($sessions->last()->was_completed)->toBeTrue();

    Date::setTestNow();
});

it('refuses to pause when no timer is running, on a break, or already paused', function () {
    [$schedule, $seat] = seatedStudent();

    $pause = fn () => $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.pause'));

    $pause()->assertStatus(422);

    $seat->update([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Break,
        'timer_started_at' => now(),
        'timer_ends_at' => now()->addMinutes(5),
    ]);

    $pause()->assertStatus(422);
    expect($seat->refresh()->paused_at)->toBeNull();

    $seat->update([
        'timer_phase' => StudyTimerPhase::Focus,
        'paused_at' => now(),
    ]);

    $pause()->assertStatus(422);
});

it('refuses to resume a timer that is not paused', function () {
    [$schedule, $seat] = seatedStudent();

    $seat->update([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Focus,
        'timer_started_at' => now(),
        'timer_ends_at' => now()->addMinutes(25),
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.resume'))
        ->assertStatus(422);
});

it('refuses to start a break while paused, even past the planned end', function () {
    [$schedule, $seat] = seatedStudent();

    $seat->update([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Focus,
        'timer_round' => 1,
        'timer_started_at' => now()->subMinutes(30),
        'timer_ends_at' => now()->subMinutes(5),
        'paused_at' => now()->subMinutes(2),
    ]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyTimerCookie($schedule))
        ->postJson(route('study-room.timer.break'))
        ->assertStatus(422);

    expect($seat->refresh()->timer_phase)->toBe(StudyTimerPhase::Focus);
});
