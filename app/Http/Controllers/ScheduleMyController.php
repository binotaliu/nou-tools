<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

final class ScheduleMyController extends Controller
{
    public function __invoke(Request $request, ReadStudentScheduleCookie $readStudentScheduleCookie): RedirectResponse
    {
        $cookie = $readStudentScheduleCookie($request);

        if ($cookie === null) {
            return redirect()->route('schedules.create');
        }

        return redirect()->route('schedules.show', $cookie->token);
    }
}
