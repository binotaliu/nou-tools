<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use Illuminate\Support\Facades\Cookie as CookieJar;
use NouTools\Domains\Schedules\DataTransferObjects\ForgetScheduleDeviceData;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\LeaveSeat;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Signs this browser out: revokes its device token and, because the browser
 * may be shared, stops everything that would keep acting on the schedule's
 * behalf here. That is the one case where a push subscription is removed;
 * the notification toggles themselves never unsubscribe (see CLAUDE.md).
 * Other devices signed in to the same schedule are untouched.
 *
 * Works for a visitor who is already signed out too, so a stale button or a
 * second tab still ends with the cookie cleared.
 */
final readonly class ForgetScheduleDevice
{
    public function __construct(private LeaveSeat $leaveSeat) {}

    public function __invoke(?StudentScheduleCookie $viewer, ?string $token, ForgetScheduleDeviceData $input): Cookie
    {
        if (is_string($token) && $token !== '') {
            ScheduleDevice::query()->where('token_hash', ScheduleDevice::hashToken($token))->delete();
        }

        if ($viewer !== null) {
            if ($input->pushEndpoint !== null && $input->pushEndpoint !== '') {
                StudentSchedule::query()->find($viewer->id)?->deletePushSubscription($input->pushEndpoint);
            }

            ($this->leaveSeat)($viewer);
        }

        // A legacy cookie converted earlier in this request queued a fresh device cookie.
        CookieJar::unqueue(BuildScheduleDeviceCookie::NAME);

        return cookie()->forget(BuildScheduleDeviceCookie::NAME);
    }
}
