<?php

namespace App\Http\Controllers;

use App\Enums\ArticleType;
use Illuminate\View\View;
use NouTools\Domains\Articles\Actions\ShowArticleIndexPage;
use NouTools\Domains\Articles\Actions\ShowArticlePage;

class ArticleController extends Controller
{
    public function index(ArticleType $type, ShowArticleIndexPage $showArticleIndexPage): View
    {
        $page = $showArticleIndexPage($type);

        abort_if($page === null, 404);

        return view('articles.index', [
            'type' => $page->type,
            'indexContent' => $page->indexContent,
        ]);
    }

    public function show(ArticleType $type, string $slug, ShowArticlePage $showArticlePage): View
    {
        $page = $showArticlePage($type, $slug);

        abort_if($page === null, 404);

        return view('articles.show', [
            'article' => $page->article,
            'sidebarContent' => $page->sidebarContent,
        ]);
    }
}
