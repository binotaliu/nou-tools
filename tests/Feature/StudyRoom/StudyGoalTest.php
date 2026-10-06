<?php

use App\Enums\StudyActivityVerb;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;

$goalCookie = function (StudentSchedule $schedule): string {
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
};

it('saves a weekly goal and per-weekday goals together', function () use ($goalCookie) {
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->create(['student_schedule_id' => $schedule->id, 'nickname' => '不動的暱稱']);

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal.update'), [
            'weeklyGoalMinutes' => 600,
            'dailyGoals' => [
                '1' => ['minutes' => 90, 'remindAt' => '20:00'],
                '2' => ['minutes' => null, 'remindAt' => null],
                '6' => ['minutes' => null, 'remindAt' => '09:30'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('goal.weeklyGoalMinutes', 600)
        ->assertJsonPath('goal.dailyGoals.1.minutes', 90)
        ->assertJsonPath('goal.dailyGoals.6.remindAt', '09:30')
        ->assertJsonMissingPath('goal.dailyGoals.2');

    expect($profile->fresh())
        ->weekly_goal_minutes->toBe(600)
        ->nickname->toBe('不動的暱稱')
        ->daily_goals->toBe([
            1 => ['minutes' => 90, 'remindAt' => '20:00'],
            6 => ['minutes' => null, 'remindAt' => '09:30'],
        ]);
});

it('clears every goal when nothing is set', function () use ($goalCookie) {
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->create([
        'student_schedule_id' => $schedule->id,
        'weekly_goal_minutes' => 300,
        'daily_goals' => [1 => ['minutes' => 60, 'remindAt' => null]],
    ]);

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal.update'), ['weeklyGoalMinutes' => null, 'dailyGoals' => []])
        ->assertOk();

    expect($profile->fresh())->weekly_goal_minutes->toBeNull()->daily_goals->toBeNull();
});

it('validates goal values', function () use ($goalCookie) {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->create(['student_schedule_id' => $schedule->id]);

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal.update'), [
            'weeklyGoalMinutes' => 0,
            'dailyGoals' => ['1' => ['minutes' => 2000, 'remindAt' => '25:99']],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['weeklyGoalMinutes', 'dailyGoals.1.minutes', 'dailyGoals.1.remindAt']);
});

it('does not create a profile or accept anonymous visitors', function () use ($goalCookie) {
    $schedule = StudentSchedule::factory()->create();
    $payload = ['weeklyGoalMinutes' => 60, 'dailyGoals' => []];

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal.update'), $payload)
        ->assertStatus(422)
        ->assertJsonPath('message', '請先到自習室設定暱稱。');

    $this->assertDatabaseCount('study_room_profiles', 0);
});

it('rejects visitors without a schedule cookie', function () {
    $this->putJson(route('study-room.goal.update'), ['weeklyGoalMinutes' => 60, 'dailyGoals' => []])->assertForbidden();
    $this->putJson(route('study-room.goal-reminder.update'), ['enabled' => true])->assertForbidden();
});

it('turns the goal reminder on and off', function () use ($goalCookie) {
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->create(['student_schedule_id' => $schedule->id]);

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal-reminder.update'), ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('goal.notifyOnGoalReminder', true);

    expect($profile->fresh()->notify_on_goal_reminder)->toBeTrue();

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal-reminder.update'), ['enabled' => false])
        ->assertOk();

    expect($profile->fresh()->notify_on_goal_reminder)->toBeFalse();
});

it('reports this week\'s focus seconds from Monday in Taipei time', function () use ($goalCookie) {
    // Wednesday
    $this->travelTo(Date::parse('2026-09-16 10:00:00', 'Asia/Taipei'));
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->create(['student_schedule_id' => $schedule->id]);

    foreach (['2026-09-13 23:00:00' => 1000, '2026-09-14 00:30:00' => 600, '2026-09-16 08:00:00' => 300] as $endedAt => $seconds) {
        StudyRoomSession::factory()->for($schedule, 'schedule')->create([
            'started_at' => Date::parse($endedAt, 'Asia/Taipei')->subSeconds($seconds)->utc(),
            'ended_at' => Date::parse($endedAt, 'Asia/Taipei')->utc(),
            'focus_seconds' => $seconds,
        ]);
    }

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->getJson(route('study-room.state'))
        ->assertOk()
        ->assertJsonPath('totals.yourFocusSecondsToday', 300)
        ->assertJsonPath('totals.yourFocusSecondsThisWeek', 900);
});

it('leaves in-person class time out of goal progress by default', function () use ($goalCookie) {
    $this->travelTo(Date::parse('2026-09-16 10:00:00', 'Asia/Taipei'));
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->create(['student_schedule_id' => $schedule->id]);

    foreach ([StudyActivityVerb::Reading, StudyActivityVerb::InPersonClass, null] as $verb) {
        StudyRoomSession::factory()->for($schedule, 'schedule')->create([
            'activity_verb' => $verb,
            'started_at' => Date::now()->subHour(),
            'ended_at' => Date::now()->subMinutes(30),
            'focus_seconds' => 600,
        ]);
    }

    $state = fn () => $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->getJson(route('study-room.state'))
        ->assertOk();

    $state()
        ->assertJsonPath('totals.yourFocusSecondsToday', 1800)
        ->assertJsonPath('totals.yourGoalSecondsToday', 1200)
        ->assertJsonPath('totals.yourGoalSecondsThisWeek', 1200);

    $this->withCredentials()->withCookie('student_schedule', $goalCookie($schedule))
        ->putJson(route('study-room.goal.update'), ['weeklyGoalMinutes' => 300, 'dailyGoals' => [], 'excludeInPersonClass' => false])
        ->assertOk()
        ->assertJsonPath('goal.excludeInPersonClass', false);

    expect($profile->fresh()->goal_excludes_in_person_class)->toBeFalse();

    $state()
        ->assertJsonPath('totals.yourGoalSecondsToday', 1800)
        ->assertJsonPath('totals.yourGoalSecondsThisWeek', 1800);
});
