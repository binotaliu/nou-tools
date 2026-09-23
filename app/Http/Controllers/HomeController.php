<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use NouTools\Domains\Home\Actions\ShowHomePage;
use NouTools\Domains\Home\DataTransferObjects\ShowHomePageData;
use NouTools\Domains\Shared\Actions\ListUpcomingSchoolEvents;
use Symfony\Component\HttpFoundation\Response;

final class HomeController extends Controller
{
    public function index(
        ShowHomePage $showHomePage,
        ShowHomePageData $input,
        Request $request,
        ListUpcomingSchoolEvents $listUpcomingSchoolEvents,
    ): Response {
        $page = $showHomePage($input, $request);

        $currentSemester = (string) config('app.current_semester');
        $range = config('app.current_semester_range', []);

        return Inertia::render('Home/Index', [
            'viewModel' => $page,
            'greeting' => [
                'semesterLabel' => Str::toSemesterDisplay($currentSemester),
                'semesterCode' => $currentSemester,
                'semesterStart' => is_array($range) && ! empty($range[0]) ? (string) $range[0] : null,
                'semesterEnd' => is_array($range) && ! empty($range[1]) ? (string) $range[1] : null,
            ],
            'schoolCalendar' => [
                'events' => $listUpcomingSchoolEvents(),
                'showPastEvents' => false,
            ],
        ])
            ->toResponse($request)
            ->header('Link', implode(', ', [
                '<'.route('docs.api.view').'>; rel="service-doc"',
                '<'.route('docs.api.yaml').'>; rel="service-desc"',
            ]));
    }
}
