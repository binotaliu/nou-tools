<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\RedirectResponse;
use NouTools\Domains\Schedules\Actions\BuildStudentScheduleCookie;

final class ScheduleRememberController extends Controller
{
    public function __invoke(StudentSchedule $schedule, BuildStudentScheduleCookie $buildStudentScheduleCookie): RedirectResponse
    {
        return redirect()->route('schedules.show', $schedule)
            ->cookie($buildStudentScheduleCookie($schedule));
    }
}
