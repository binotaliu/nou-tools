<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\Home\Actions\ShowHomePage;
use NouTools\Domains\Home\DataTransferObjects\ShowHomePageData;

class HomeController extends Controller
{
    public function index(ShowHomePage $showHomePage, ShowHomePageData $input, Request $request): View
    {
        $page = $showHomePage($input, $request);

        return view('home', [
            'selectedDate' => $page->selectedDate,
            'courses' => $page->courses,
            'previousSchedule' => $page->previousSchedule,
        ]);
    }
}
