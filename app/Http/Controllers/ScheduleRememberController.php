<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\Schedules\Actions\RememberScheduleDevice;
use NouTools\Domains\Schedules\DataTransferObjects\RememberScheduleData;

final class ScheduleRememberController extends Controller
{
    public function __invoke(StudentSchedule $schedule, RememberScheduleData $input, Request $request, RememberScheduleDevice $rememberScheduleDevice): RedirectResponse
    {
        $cookie = $rememberScheduleDevice(
            $schedule,
            $input->remember,
            $request->userAgent(),
            $request->cookie(BuildScheduleDeviceCookie::NAME),
        );

        return redirect()->route('schedules.show', $schedule)->cookie($cookie);
    }
}
