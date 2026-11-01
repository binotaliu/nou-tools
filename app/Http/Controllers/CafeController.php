<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Cafe\Actions\ShowCafePage;

final class CafeController extends Controller
{
    /**
     * The shell only: live seats come from GET /cafe/state and the `cafe`
     * Echo channel, for the same reason the study room does it.
     */
    public function show(Request $request, ShowCafePage $showCafePage): Response
    {
        return Inertia::render('Cafe/Show', [
            'cafe' => $showCafePage($request),
        ]);
    }
}
