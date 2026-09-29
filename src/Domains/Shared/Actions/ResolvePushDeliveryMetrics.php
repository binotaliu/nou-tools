<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\PushNotificationDelivery;
use Illuminate\Support\Facades\Date;

final readonly class ResolvePushDeliveryMetrics
{
    /**
     * Web push delivery outcomes over the last N days, with failures
     * grouped by reason (most common first).
     *
     * @return array{succeeded: int, failed: int, failureReasons: array<string, int>}
     */
    public function __invoke(int $days = 7): array
    {
        $deliveries = PushNotificationDelivery::query()
            ->where('created_at', '>=', Date::now()->subDays($days))
            ->get(['success', 'reason']);

        $failures = $deliveries->where('success', false);

        return [
            'succeeded' => $deliveries->where('success', true)->count(),
            'failed' => $failures->count(),
            'failureReasons' => $failures
                ->countBy(fn (PushNotificationDelivery $delivery): string => $delivery->reason ?: '未知原因')
                ->sortDesc()
                ->all(),
        ];
    }
}
