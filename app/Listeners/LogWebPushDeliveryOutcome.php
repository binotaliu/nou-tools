<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\PushNotificationDelivery;
use NotificationChannels\WebPush\Events\NotificationFailed;
use NotificationChannels\WebPush\Events\NotificationSent;
use NotificationChannels\WebPush\PushSubscription;

/**
 * Every web push send (per subscribed device) previously vanished silently
 * on failure: the package only fires an event, and nothing listened for it.
 * This records every outcome so failures are visible and can be reasoned
 * about, instead of looking identical to "never attempted".
 */
final readonly class LogWebPushDeliveryOutcome
{
    public function handleSent(NotificationSent $event): void
    {
        $this->log($event->subscription, success: true, reason: null);
    }

    public function handleFailed(NotificationFailed $event): void
    {
        $this->log($event->subscription, success: false, reason: $event->report->getReason());
    }

    private function log(PushSubscription $subscription, bool $success, ?string $reason): void
    {
        PushNotificationDelivery::query()->create([
            'subscribable_type' => $subscription->subscribable_type,
            'subscribable_id' => $subscription->subscribable_id,
            'endpoint' => $subscription->endpoint,
            'success' => $success,
            'reason' => $reason,
        ]);
    }
}
