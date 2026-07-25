<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NouTools\Domains\Home\Actions\ShowHomePage;
use NouTools\Domains\Home\DataTransferObjects\ShowHomePageData;

class HomeController extends Controller
{
    public function index(ShowHomePage $showHomePage, ShowHomePageData $input, Request $request): Response
    {
        $page = $showHomePage($input, $request);

        return response()
            ->view('home', [
                'selectedDate' => $page->selectedDate,
                'courses' => $page->courses,
                'previousSchedule' => $page->previousSchedule,
            ])
            ->header('Link', implode(', ', [
                '<'.route('docs.api.view').'>; rel="service-doc"',
                '<'.route('docs.api.yaml').'>; rel="service-desc"',
            ]));
    }
}
