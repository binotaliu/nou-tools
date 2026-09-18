<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NewsletterIssue;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Newsletter\Actions\ShowNewsletterIndexPage;
use NouTools\Domains\Newsletter\Actions\ShowNewsletterIssuePage;

final class NewsletterController extends Controller
{
    public function index(ShowNewsletterIndexPage $showNewsletterIndexPage): Response
    {
        return Inertia::render('Newsletter/Index', [
            'viewModel' => $showNewsletterIndexPage(),
        ]);
    }

    public function show(string $issueKey, ShowNewsletterIssuePage $showNewsletterIssuePage): Response
    {
        $page = $showNewsletterIssuePage($issueKey, includeUnpublished: Gate::allows('viewAny', NewsletterIssue::class));

        abort_if($page === null, 404);

        return Inertia::render('Newsletter/Show', [
            'viewModel' => $page,
        ]);
    }
}
