<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\ShowWikiGuidePage;

final class WikiGuideController extends Controller
{
    public function __invoke(string $guide, ShowWikiGuidePage $showWikiGuidePage): Response
    {
        $page = $showWikiGuidePage($guide);

        abort_if($page === null, 404);

        return Inertia::render('Wiki/Guide', [
            'viewModel' => $page,
        ]);
    }
}
