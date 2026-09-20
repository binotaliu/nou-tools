<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use NouTools\Domains\Schedules\Actions\BuildStudentScheduleCookie;
use NouTools\Domains\Schedules\Actions\FindScheduleFromLink;
use NouTools\Domains\Schedules\DataTransferObjects\RememberScheduleFromLinkData;

final class ScheduleMyStoreController extends Controller
{
    public function __invoke(
        RememberScheduleFromLinkData $input,
        FindScheduleFromLink $findScheduleFromLink,
        BuildStudentScheduleCookie $buildStudentScheduleCookie,
    ): RedirectResponse {
        $schedule = $findScheduleFromLink($input);

        return redirect()->route('schedules.show', $schedule)
            ->cookie($buildStudentScheduleCookie($schedule));
    }
}
