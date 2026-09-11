<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Settings\StudyRoomSettings;

function studyRoomProfileCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

it('creates a profile on the happy path', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomProfileCookie($schedule))
        ->post(route('study-room.profile.update'), [
            'nickname' => '認真讀書中',
            'emoji' => config('study-room.emojis')[0],
        ]);

    $response->assertRedirect()->assertSessionHas('success', '暱稱已更新');

    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '認真讀書中',
        'emoji' => config('study-room.emojis')[0],
    ]);
});

it('rejects an emoji outside the allowlist', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomProfileCookie($schedule))
        ->post(route('study-room.profile.update'), [
            'nickname' => '認真讀書中',
            'emoji' => '💀',
        ]);

    $response->assertSessionHasErrors('emoji');
    $this->assertDatabaseMissing(StudyRoomProfile::class, ['student_schedule_id' => $schedule->id]);
});

it('rejects a forbidden nickname, including a whitespace-evasion variant', function () {
    $settings = app(StudyRoomSettings::class);
    $settings->forbiddenNicknames = ['壞字詞'];
    $settings->save();
    app()->forgetInstance(StudyRoomSettings::class);

    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomProfileCookie($schedule))
        ->post(route('study-room.profile.update'), [
            'nickname' => '我是壞字詞啦',
            'emoji' => config('study-room.emojis')[0],
        ]);

    $response->assertSessionHasErrors('nickname');

    $evasionResponse = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomProfileCookie($schedule))
        ->post(route('study-room.profile.update'), [
            'nickname' => '我是 壞 字 詞 啦',
            'emoji' => config('study-room.emojis')[0],
        ]);

    $evasionResponse->assertSessionHasErrors('nickname');
    $this->assertDatabaseMissing(StudyRoomProfile::class, ['student_schedule_id' => $schedule->id]);
});

it('blocks a second nickname change within the cooldown, then allows it after the cooldown', function () {
    $schedule = StudentSchedule::factory()->create();
    $cookie = studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '第一個暱稱',
        'emoji' => config('study-room.emojis')[0],
    ])->assertSessionHasNoErrors();

    $blocked = $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '第二個暱稱',
        'emoji' => config('study-room.emojis')[0],
    ]);

    $blocked->assertSessionHasErrors('nickname');
    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '第一個暱稱',
    ]);

    $this->travel(8)->days();

    $allowed = $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '第二個暱稱',
        'emoji' => config('study-room.emojis')[0],
    ]);

    $allowed->assertSessionHasNoErrors()->assertSessionHas('success');
    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '第二個暱稱',
    ]);
});

it('allows an emoji-only change during the nickname cooldown', function () {
    $schedule = StudentSchedule::factory()->create();
    $cookie = studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '保持不變的暱稱',
        'emoji' => config('study-room.emojis')[0],
    ])->assertSessionHasNoErrors();

    $response = $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '保持不變的暱稱',
        'emoji' => config('study-room.emojis')[1],
    ]);

    $response->assertSessionHasNoErrors()->assertSessionHas('success');
    $this->assertDatabaseHas(StudyRoomProfile::class, [
        'student_schedule_id' => $schedule->id,
        'nickname' => '保持不變的暱稱',
        'emoji' => config('study-room.emojis')[1],
    ]);
});

it('allows re-submitting the identical nickname during the cooldown', function () {
    $schedule = StudentSchedule::factory()->create();
    $cookie = studyRoomProfileCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '相同的暱稱',
        'emoji' => config('study-room.emojis')[0],
    ])->assertSessionHasNoErrors();

    $response = $this->withCredentials()->withCookie('student_schedule', $cookie)->post(route('study-room.profile.update'), [
        'nickname' => '相同的暱稱',
        'emoji' => config('study-room.emojis')[0],
    ]);

    $response->assertSessionHasNoErrors()->assertSessionHas('success');
});

it('redirects to schedule creation when there is no cookie', function () {
    $response = $this->post(route('study-room.profile.update'), [
        'nickname' => '沒有課表',
        'emoji' => config('study-room.emojis')[0],
    ]);

    $response->assertRedirect(route('schedules.create'));
});
