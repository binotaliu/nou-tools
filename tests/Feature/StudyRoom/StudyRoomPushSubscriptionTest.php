<?php

use App\Models\StudentSchedule;

function studyRoomPushCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

it('subscribes the viewer resolved from the study room cookie', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCredentials()
        ->withCookie('student_schedule', studyRoomPushCookie($schedule))
        ->postJson(route('study-room.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/study-room-1',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_type' => StudentSchedule::class,
        'subscribable_id' => $schedule->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/study-room-1',
        'public_key' => 'p256dh-key',
        'auth_token' => 'auth-token',
    ]);
});

it('rejects a visitor without a study room cookie', function () {
    $this->postJson(route('study-room.push-subscriptions.store'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/study-room-2',
        'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
    ])->assertForbidden();

    $this->assertDatabaseCount('push_subscriptions', 0);
});

it('rejects an incomplete subscription payload', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCredentials()
        ->withCookie('student_schedule', studyRoomPushCookie($schedule))
        ->postJson(route('study-room.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/study-room-3',
            'keys' => ['p256dh' => 'p256dh-key'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['keys.auth']);
});

it('reuses the one subscription a browser holds rather than adding a second', function () {
    $schedule = StudentSchedule::factory()->create();

    $schedule->updatePushSubscription(
        endpoint: 'https://fcm.googleapis.com/fcm/send/shared',
        key: 'old-key',
        token: 'old-token',
    );

    $this->withCredentials()
        ->withCookie('student_schedule', studyRoomPushCookie($schedule))
        ->postJson(route('study-room.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared',
            'keys' => ['p256dh' => 'new-key', 'auth' => 'new-token'],
        ])
        ->assertOk();

    $this->assertDatabaseCount('push_subscriptions', 1);
    $this->assertDatabaseHas('push_subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared',
        'public_key' => 'new-key',
    ]);
});
