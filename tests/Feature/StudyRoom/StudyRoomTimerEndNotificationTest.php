<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;

function timerEndNotificationCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

it('turns the timer-end notification on and off without touching the rest of the profile', function () {
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->create([
        'student_schedule_id' => $schedule->id,
        'nickname' => '不動的暱稱',
        'notify_on_timer_end' => false,
    ]);
    $cookie = timerEndNotificationCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->putJson(route('study-room.timer-end-notification.update'), ['enabled' => true])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($profile->fresh())
        ->notify_on_timer_end->toBeTrue()
        ->nickname->toBe('不動的暱稱');

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->putJson(route('study-room.timer-end-notification.update'), ['enabled' => false])
        ->assertOk();

    expect($profile->fresh()->notify_on_timer_end)->toBeFalse();
});

it('does not create a profile for a student without a nickname', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCredentials()->withCookie('student_schedule', timerEndNotificationCookie($schedule))
        ->putJson(route('study-room.timer-end-notification.update'), ['enabled' => true])
        ->assertStatus(422)
        ->assertJsonPath('message', '請先到自習室設定暱稱。');

    $this->assertDatabaseCount('study_room_profiles', 0);
});

it('rejects a visitor without a schedule cookie', function () {
    $this->putJson(route('study-room.timer-end-notification.update'), ['enabled' => true])
        ->assertForbidden();
});

it('requires the enabled flag', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCredentials()->withCookie('student_schedule', timerEndNotificationCookie($schedule))
        ->putJson(route('study-room.timer-end-notification.update'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['enabled']);
});
