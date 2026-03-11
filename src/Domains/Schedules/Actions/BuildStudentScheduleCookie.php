<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Symfony\Component\HttpFoundation\Cookie;

final class BuildStudentScheduleCookie
{
    public function __invoke(StudentSchedule $schedule): Cookie
    {
        return cookie()->forever('student_schedule', json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]));
    }
}
