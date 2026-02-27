<?php

namespace App\Http\Controllers;

use App\Enums\ArticleType;
use App\Services\ArticleService;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        public ArticleService $articleService
    ) {}

    public function index(ArticleType $type): View
    {
        $indexContent = $this->articleService->getIndex($type);

        abort_if($indexContent === null, 404);

        return view('articles.index', [
            'type' => $type,
            'indexContent' => $indexContent,
        ]);
    }

    public function show(ArticleType $type, string $slug): View
    {
        $article = $this->articleService->getArticle($type, $slug);

        abort_if($article === null, 404);

        $sidebarContent = $this->articleService->getSidebar($type);

        return view('articles.show', [
            'article' => $article,
            'sidebarContent' => $sidebarContent,
        ]);
    }
}
