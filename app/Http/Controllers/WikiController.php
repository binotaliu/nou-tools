<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\FindWikiPageBySlug;
use NouTools\Domains\Wiki\Actions\ShowWikiAllPagesPage;
use NouTools\Domains\Wiki\Actions\ShowWikiIndexPage;
use NouTools\Domains\Wiki\Actions\ShowWikiPage;

final class WikiController extends Controller
{
    public function index(Request $request, ShowWikiIndexPage $showWikiIndexPage): Response
    {
        return Inertia::render('Wiki/Index', [
            'viewModel' => $showWikiIndexPage((string) $request->query('q', '')),
        ]);
    }

    public function all(ShowWikiAllPagesPage $showWikiAllPagesPage): Response
    {
        return Inertia::render('Wiki/All', [
            'viewModel' => $showWikiAllPagesPage(),
        ]);
    }

    public function show(string $slug, FindWikiPageBySlug $findWikiPageBySlug, ShowWikiPage $showWikiPage): Response|RedirectResponse
    {
        $page = $findWikiPageBySlug($slug);

        abort_if($page === null, 404);

        if ($page->isMainPage()) {
            return redirect()->route('wiki.index');
        }

        if ($page->slug !== $slug) {
            return redirect()->route('wiki.show', $page->slug, 301);
        }

        return Inertia::render('Wiki/Show', [
            'viewModel' => $showWikiPage($page),
        ]);
    }
}
