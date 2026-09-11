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
use Illuminate\Support\Facades\Date;

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
