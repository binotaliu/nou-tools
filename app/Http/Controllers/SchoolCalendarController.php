<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\SchoolCalendar\Actions\ShowSchoolCalendarPage;
use NouTools\Domains\SchoolCalendar\DataTransferObjects\ShowSchoolCalendarData;

final class SchoolCalendarController extends Controller
{
    public function index(ShowSchoolCalendarPage $showSchoolCalendarPage, ShowSchoolCalendarData $input): Response
    {
        return Inertia::render('SchoolCalendar/Index', [
            'viewModel' => $showSchoolCalendarPage($input),
        ]);
    }
}
