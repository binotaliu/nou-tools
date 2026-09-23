<?php

use App\Models\PushNotificationDelivery;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\Events\NotificationFailed;
use NotificationChannels\WebPush\Events\NotificationSent;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\WebPushMessage;

$pushSubscriptionFor = function (string $endpoint): PushSubscription {
    $subscription = new PushSubscription;
    $subscription->forceFill([
        'subscribable_type' => 'App\\Models\\StudentSchedule',
        'subscribable_id' => 1,
        'endpoint' => $endpoint,
        'public_key' => 'p256dh-key',
        'auth_token' => 'auth-token',
    ]);

    return $subscription;
};

it('records a successful web push delivery', function () use ($pushSubscriptionFor) {
    $subscription = $pushSubscriptionFor('https://fcm.googleapis.com/fcm/send/success');
    $report = new MessageSentReport(new PsrRequest('POST', $subscription->endpoint));

    event(new NotificationSent($report, $subscription, new WebPushMessage));

    $this->assertDatabaseHas(PushNotificationDelivery::class, [
        'subscribable_type' => 'App\\Models\\StudentSchedule',
        'subscribable_id' => '1',
        'endpoint' => $subscription->endpoint,
        'success' => true,
        'reason' => null,
    ]);
});

it('records a failed web push delivery with its reason', function () use ($pushSubscriptionFor) {
    $subscription = $pushSubscriptionFor('https://fcm.googleapis.com/fcm/send/failure');
    $report = new MessageSentReport(new PsrRequest('POST', $subscription->endpoint), success: false, reason: 'Gone');

    event(new NotificationFailed($report, $subscription, new WebPushMessage));

    $this->assertDatabaseHas(PushNotificationDelivery::class, [
        'subscribable_type' => 'App\\Models\\StudentSchedule',
        'subscribable_id' => '1',
        'endpoint' => $subscription->endpoint,
        'success' => false,
        'reason' => 'Gone',
    ]);
});
