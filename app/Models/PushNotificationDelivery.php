<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An immutable audit record of a single web push delivery attempt to a
 * single subscribed device, so failures (rejected/expired subscriptions,
 * transient push-service errors) are visible instead of silently dropped.
 */
final class PushNotificationDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subscribable_type',
        'subscribable_id',
        'endpoint',
        'success',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
