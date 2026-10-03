<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\Schedules\Actions\ForgetScheduleDevice;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;
use NouTools\Domains\Schedules\DataTransferObjects\ForgetScheduleDeviceData;

final class ScheduleDeviceDestroyController extends Controller
{
    public function __invoke(Request $request, ForgetScheduleDeviceData $input, ForgetScheduleDevice $forgetScheduleDevice): RedirectResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        $cookie = $forgetScheduleDevice(
            $viewer,
            $request->cookie(BuildScheduleDeviceCookie::NAME) ?? $request->attributes->get(ReadStudentScheduleCookie::MINTED_TOKEN_ATTRIBUTE),
            $input,
        );

        return redirect()->route('settings')->cookie($cookie);
    }
}
