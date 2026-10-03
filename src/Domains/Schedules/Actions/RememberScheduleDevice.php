<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\DataTransferObjects\IssueScheduleDeviceData;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class RememberScheduleDevice
{
    public function __construct(private BuildScheduleDeviceCookie $buildScheduleDeviceCookie) {}

    /**
     * Signs a browser in to the schedule. The returned cookie carries the raw
     * token, which exists nowhere else once this returns. A device the browser
     * already held (`$replacesToken`) is revoked so signing in as someone else
     * leaves no live session behind.
     */
    public function __invoke(StudentSchedule $schedule, bool $isPersistent, ?string $userAgent, ?string $replacesToken = null): Cookie
    {
        if (is_string($replacesToken) && $replacesToken !== '') {
            ScheduleDevice::query()->where('token_hash', ScheduleDevice::hashToken($replacesToken))->delete();
        }

        $token = Str::random(64);
        $now = now();

        (new ScheduleDevice)->fillFromDTO(new IssueScheduleDeviceData(
            studentScheduleId: $schedule->id,
            token: $token,
            isPersistent: $isPersistent,
            userAgent: $userAgent === null ? null : Str::limit($userAgent, 250, ''),
            issuedAt: $now,
            expiresAt: $isPersistent
                ? $now->copy()->addDays(BuildScheduleDeviceCookie::PERSISTENT_DAYS)
                : $now->copy()->addHours(BuildScheduleDeviceCookie::SESSION_HOURS),
        ))->saveOrFail();

        return ($this->buildScheduleDeviceCookie)($token, $isPersistent);
    }
}
