<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\ScheduleDevice;
use NouTools\Domains\Schedules\DataTransferObjects\RestoreScheduleDeviceData;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Puts a device's cookie back from the copy the page keeps in local storage,
 * for when the browser dropped the cookie (Safari's storage limits, eviction)
 * but not the storage. Only a still-valid, remembered device can be restored,
 * so signing out (which revokes the row) can never be undone from storage.
 */
final readonly class RestoreScheduleDevice
{
    public function __construct(private BuildScheduleDeviceCookie $buildScheduleDeviceCookie) {}

    public function __invoke(RestoreScheduleDeviceData $input): ?Cookie
    {
        /** @var ScheduleDevice|null $device */
        $device = ScheduleDevice::query()
            ->active()
            ->where('is_persistent', true)
            ->where('token_hash', ScheduleDevice::hashToken($input->token))
            ->first();

        if (! $device) {
            return null;
        }

        $device->last_used_at = now();
        $device->expires_at = now()->addDays(BuildScheduleDeviceCookie::PERSISTENT_DAYS);
        $device->saveOrFail();

        return ($this->buildScheduleDeviceCookie)($input->token, true);
    }
}
