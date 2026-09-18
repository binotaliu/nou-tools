<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\PushNotificationDelivery;
use NotificationChannels\WebPush\Events\NotificationFailed;

/**
 * Web push delivery failures previously vanished silently: the package
 * only fires this event, and nothing listened for it. Recording the
 * reason (rejected/expired subscription, transient push-service error,
 * ...) so a failure is visible instead of looking identical to a send
 * that never happened.
 */
final readonly class LogWebPushNotificationFailed
{
    public function handle(NotificationFailed $event): void
    {
        PushNotificationDelivery::query()->create([
            'subscribable_type' => $event->subscription->subscribable_type,
            'subscribable_id' => $event->subscription->subscribable_id,
            'endpoint' => $event->subscription->endpoint,
            'success' => false,
            'reason' => $event->report->getReason(),
        ]);
    }
}
