<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChangelogPost;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Changelog\Actions\ShowChangelogIndexPage;
use NouTools\Domains\Changelog\Actions\ShowChangelogPostPage;

final class ChangelogController extends Controller
{
    public function index(ShowChangelogIndexPage $showChangelogIndexPage): Response
    {
        return Inertia::render('Changelog/Index', [
            'viewModel' => $showChangelogIndexPage(),
        ]);
    }

    public function show(string $slug, ShowChangelogPostPage $showChangelogPostPage): Response
    {
        $page = $showChangelogPostPage($slug, includeUnpublished: Gate::allows('viewAny', ChangelogPost::class));

        abort_if($page === null, 404);

        return Inertia::render('Changelog/Show', [
            'viewModel' => $page,
        ]);
    }
}
