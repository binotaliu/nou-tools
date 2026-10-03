<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\Schedules\Actions\FindScheduleFromLink;
use NouTools\Domains\Schedules\Actions\RememberScheduleDevice;
use NouTools\Domains\Schedules\DataTransferObjects\RememberScheduleFromLinkData;

final class ScheduleMyStoreController extends Controller
{
    public function __invoke(
        RememberScheduleFromLinkData $input,
        Request $request,
        FindScheduleFromLink $findScheduleFromLink,
        RememberScheduleDevice $rememberScheduleDevice,
    ): RedirectResponse {
        $schedule = $findScheduleFromLink($input);

        $cookie = $rememberScheduleDevice(
            $schedule,
            $input->remember,
            $request->userAgent(),
            $request->cookie(BuildScheduleDeviceCookie::NAME),
        );

        return redirect()->route('schedules.show', $schedule)->cookie($cookie);
    }
}
