<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

it('subscribes a schedule to push notifications', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '推播測試',
    ]);

    $payload = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => [
            'p256dh' => 'p256dh-key',
            'auth' => 'auth-token',
        ],
    ];

    $this->postJson(route('schedules.push-subscriptions.store', $schedule), $payload)
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_type' => StudentSchedule::class,
        'subscribable_id' => $schedule->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'public_key' => 'p256dh-key',
        'auth_token' => 'auth-token',
    ]);
});

it('rejects an invalid push subscription payload', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '推播測試',
    ]);

    $payload = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => [
            'p256dh' => 'p256dh-key',
        ],
    ];

    $this->postJson(route('schedules.push-subscriptions.store', $schedule), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['keys.auth']);
});

it('unsubscribes a schedule from push notifications', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '推播測試',
    ]);

    $schedule->updatePushSubscription(
        endpoint: 'https://fcm.googleapis.com/fcm/send/abc123',
        key: 'p256dh-key',
        token: 'auth-token',
    );

    $this->deleteJson(route('schedules.push-subscriptions.destroy', $schedule), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('push_subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ]);
});
