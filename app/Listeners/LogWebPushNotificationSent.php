<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\PushNotificationDelivery;
use NotificationChannels\WebPush\Events\NotificationSent;

/**
 * Web push delivery outcomes previously vanished silently: the package
 * only fires this event, and nothing listened for it. Recording every
 * successful send too (not just failures) lets a per-device failure be
 * told apart from "never attempted".
 */
final readonly class LogWebPushNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        PushNotificationDelivery::query()->create([
            'subscribable_type' => $event->subscription->subscribable_type,
            'subscribable_id' => $event->subscription->subscribable_id,
            'endpoint' => $event->subscription->endpoint,
            'success' => true,
            'reason' => null,
        ]);
    }
}
