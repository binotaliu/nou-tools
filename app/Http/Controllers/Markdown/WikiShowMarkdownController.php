<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\Wiki\Actions\FindWikiPageBySlug;

final class WikiShowMarkdownController extends Controller
{
    public function __invoke(string $slug, FindWikiPageBySlug $findWikiPageBySlug): Response
    {
        $page = $findWikiPageBySlug($slug);

        abort_if($page === null, 404);

        return response()
            ->view('wiki.markdown.show', [
                'page' => $page,
                'revision' => $page->currentRevision,
                'wikiTitle' => config('wiki.title'),
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
