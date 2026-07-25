<?php

namespace App\Http\Controllers\Markdown;

use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use NouTools\Domains\Articles\Actions\ShowArticleIndexPage;

class ArticleIndexMarkdownController extends Controller
{
    public function __invoke(ArticleType $type, ShowArticleIndexPage $showArticleIndexPage): Response
    {
        $page = $showArticleIndexPage($type);

        abort_if($page === null, 404);

        // The index file is already plain Markdown with no front matter, so it
        // can be passed straight through instead of re-deriving it from HTML.
        $indexContent = File::get(resource_path("articles/{$type->directory()}/_index.md"));

        return response()
            ->view('articles.markdown.index', [
                'type' => $page->type,
                'indexContent' => $indexContent,
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
