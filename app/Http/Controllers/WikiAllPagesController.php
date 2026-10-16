<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\ShowWikiAllPagesPage;

final class WikiAllPagesController extends Controller
{
    public function __invoke(ShowWikiAllPagesPage $showWikiAllPagesPage): Response
    {
        return Inertia::render('Wiki/All', [
            'viewModel' => $showWikiAllPagesPage(),
        ]);
    }
}
