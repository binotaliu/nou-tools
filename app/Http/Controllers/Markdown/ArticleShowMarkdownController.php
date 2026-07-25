<?php

namespace App\Http\Controllers\Markdown;

use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use NouTools\Domains\Articles\Actions\ShowArticlePage;

class ArticleShowMarkdownController extends Controller
{
    public function __invoke(ArticleType $type, string $slug, ShowArticlePage $showArticlePage): Response
    {
        $page = $showArticlePage($type, $slug);

        abort_if($page === null, 404);

        // Reuse the same front-matter parser the HTML action uses, but stop
        // before Markdown-to-HTML conversion so the body stays raw Markdown.
        $rawFile = File::get(resource_path("articles/{$type->directory()}/{$slug}.md"));
        $rawContent = (new FrontMatterParser(new SymfonyYamlFrontMatterParser))->parse($rawFile)->getContent();

        return response()
            ->view('articles.markdown.show', [
                'article' => $page->article,
                'rawContent' => $rawContent,
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
