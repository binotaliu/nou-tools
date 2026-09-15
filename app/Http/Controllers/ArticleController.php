<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ArticleType;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Articles\Actions\ShowArticleIndexPage;
use NouTools\Domains\Articles\Actions\ShowArticlePage;

final class ArticleController extends Controller
{
    public function index(ArticleType $type, ShowArticleIndexPage $showArticleIndexPage): Response
    {
        $page = $showArticleIndexPage($type);

        abort_if($page === null, 404);

        return Inertia::render('Articles/Index', [
            'viewModel' => $page,
        ]);
    }

    public function show(ArticleType $type, string $slug, ShowArticlePage $showArticlePage): Response
    {
        $page = $showArticlePage($type, $slug);

        abort_if($page === null, 404);

        return Inertia::render('Articles/Show', [
            'viewModel' => $page,
        ]);
    }
}
