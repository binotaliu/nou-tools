<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Settings\StudyRoomSettings;

$studyRoomProfileCookie = function (StudentSchedule $schedule): string {
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
};

it('creates a profile on the happy path', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomProfileCookie($schedule))
        ->postJson(route('study-room.profile.update'), [
            'nickname' => '認真讀書中',
            'emoji' => config('study-room.emojis')[0],
            'playSoundOnTimerEnd' => true,
            'notifyOnTimerEnd' => false,
        ]);

    $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('message', '暱稱已更新');

    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '認真讀書中',
        'emoji' => config('study-room.emojis')[0],
        'play_sound_on_timer_end' => true,
    ]);
});

it('saves the play-sound-on-timer-end preference, including turning it off', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();
    $cookie = $studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '愛聽音效',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => false,
        'notifyOnTimerEnd' => false,
    ])->assertOk();

    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'play_sound_on_timer_end' => false,
    ]);
});

it('saves the notify-on-timer-end preference, including turning it back off', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();
    $cookie = $studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '想收通知',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => true,
    ])->assertOk();

    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'notify_on_timer_end' => true,
    ]);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '想收通知',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ])->assertOk();

    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'notify_on_timer_end' => false,
    ]);
});

it('rejects an emoji outside the allowlist', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomProfileCookie($schedule))
        ->postJson(route('study-room.profile.update'), [
            'nickname' => '認真讀書中',
            'emoji' => '💀',
            'playSoundOnTimerEnd' => true,
            'notifyOnTimerEnd' => false,
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors('emoji');
    $this->assertDatabaseMissing(StudyRoomProfile::class, ['student_schedule_id' => $schedule->id]);
});

it('rejects a forbidden nickname, including a whitespace-evasion variant', function () use ($studyRoomProfileCookie) {
    $settings = app(StudyRoomSettings::class);
    $settings->forbiddenNicknames = ['壞字詞'];
    $settings->save();
    app()->forgetInstance(StudyRoomSettings::class);

    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomProfileCookie($schedule))
        ->postJson(route('study-room.profile.update'), [
            'nickname' => '我是壞字詞啦',
            'emoji' => config('study-room.emojis')[0],
            'playSoundOnTimerEnd' => true,
            'notifyOnTimerEnd' => false,
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors('nickname');

    $evasionResponse = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomProfileCookie($schedule))
        ->postJson(route('study-room.profile.update'), [
            'nickname' => '我是 壞 字 詞 啦',
            'emoji' => config('study-room.emojis')[0],
            'playSoundOnTimerEnd' => true,
            'notifyOnTimerEnd' => false,
        ]);

    $evasionResponse->assertStatus(422)->assertJsonValidationErrors('nickname');
    $this->assertDatabaseMissing(StudyRoomProfile::class, ['student_schedule_id' => $schedule->id]);
});

it('blocks a second nickname change within the cooldown, then allows it after the cooldown', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();
    $cookie = $studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '第一個暱稱',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ])->assertOk();

    $blocked = $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '第二個暱稱',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ]);

    $blocked->assertStatus(422)->assertJsonValidationErrors('nickname');
    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '第一個暱稱',
    ]);

    $this->travel(8)->days();

    $allowed = $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '第二個暱稱',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ]);

    $allowed->assertOk()->assertJsonPath('ok', true);
    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '第二個暱稱',
    ]);
});

it('allows an emoji-only change during the nickname cooldown', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();
    $cookie = $studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '保持不變的暱稱',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ])->assertOk();

    $response = $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '保持不變的暱稱',
        'emoji' => config('study-room.emojis')[1],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '保持不變的暱稱',
        'emoji' => config('study-room.emojis')[1],
    ]);
});

it('allows re-submitting the identical nickname during the cooldown', function () use ($studyRoomProfileCookie) {
    $schedule = StudentSchedule::factory()->create();
    $cookie = $studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '相同的暱稱',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ])->assertOk();

    $response = $this->withCredentials()->withCookie('student_schedule', $cookie)->postJson(route('study-room.profile.update'), [
        'nickname' => '相同的暱稱',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ]);

    $response->assertOk()->assertJsonPath('ok', true);
});

it('returns a redirect hint to schedule creation when there is no cookie', function () {
    $response = $this->postJson(route('study-room.profile.update'), [
        'nickname' => '沒有課表',
        'emoji' => config('study-room.emojis')[0],
        'playSoundOnTimerEnd' => true,
        'notifyOnTimerEnd' => false,
    ]);

    $response->assertStatus(403)->assertJsonPath('redirect', route('schedules.create'));
});
