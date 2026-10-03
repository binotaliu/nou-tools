<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Resolves which schedule the viewer is signed in to.
 *
 * Reads the opaque `schedule_device` cookie first. A visitor who still has the
 * legacy `student_schedule` cookie (the schedule's id and uuid, kept forever)
 * is converted to a device on the spot, and the legacy cookie is expired.
 */
final readonly class ReadStudentScheduleCookie
{
    private const RESOLVED_ATTRIBUTE = 'student_schedule_cookie';

    private const RENEW_AFTER_DAYS = 1;

    public function __construct(
        private BuildScheduleDeviceCookie $buildScheduleDeviceCookie,
        private RememberScheduleDevice $rememberScheduleDevice,
    ) {}

    public function __invoke(Request $request): ?StudentScheduleCookie
    {
        // Called many times per request (page props, policies, actions); resolve once.
        if ($request->attributes->has(self::RESOLVED_ATTRIBUTE)) {
            return $request->attributes->get(self::RESOLVED_ATTRIBUTE);
        }

        $resolved = $this->fromDevice($request) ?? $this->fromLegacyCookie($request);
        $request->attributes->set(self::RESOLVED_ATTRIBUTE, $resolved);

        return $resolved;
    }

    private function fromDevice(Request $request): ?StudentScheduleCookie
    {
        $token = $request->cookie(BuildScheduleDeviceCookie::NAME);

        if (! is_string($token) || $token === '') {
            return null;
        }

        /** @var ScheduleDevice|null $device */
        $device = ScheduleDevice::query()
            ->active()
            ->with('studentSchedule')
            ->where('token_hash', ScheduleDevice::hashToken($token))
            ->first();

        if (! $device || ! $device->studentSchedule) {
            return null;
        }

        $this->renewIfStale($device, $token);

        return StudentScheduleCookie::fromModel($device->studentSchedule);
    }

    /**
     * Sliding expiry: every use pushes the end date out and re-issues the
     * cookie, so a browser that caps cookie age still sees a fresh one.
     */
    private function renewIfStale(ScheduleDevice $device, string $token): void
    {
        if ($device->last_used_at->gt(now()->subDays(self::RENEW_AFTER_DAYS))) {
            return;
        }

        $device->last_used_at = now();
        $device->expires_at = $device->is_persistent
            ? now()->addDays(BuildScheduleDeviceCookie::PERSISTENT_DAYS)
            : now()->addHours(BuildScheduleDeviceCookie::SESSION_HOURS);
        $device->saveOrFail();

        Cookie::queue(($this->buildScheduleDeviceCookie)($token, $device->is_persistent));
    }

    private function fromLegacyCookie(Request $request): ?StudentScheduleCookie
    {
        $cookie = $request->cookie('student_schedule');

        if (! $cookie) {
            return null;
        }

        $data = json_decode($cookie, true);

        if (! is_array($data) || ! isset($data['id'], $data['uuid'])) {
            return null;
        }

        /** @var StudentSchedule|null $model */
        $model = StudentSchedule::query()->find($data['id']);

        if (! $model) {
            return null;
        }

        Cookie::queue(($this->rememberScheduleDevice)($model, true, $request->userAgent()));
        Cookie::queue(Cookie::forget('student_schedule'));

        return StudentScheduleCookie::fromModel($model);
    }
}
