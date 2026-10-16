<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\Wiki\Actions\ShowWikiIndexPage;

final class WikiIndexMarkdownController extends Controller
{
    public function __invoke(ShowWikiIndexPage $showWikiIndexPage): Response
    {
        return response()
            ->view('wiki.markdown.index', [
                'viewModel' => $showWikiIndexPage(),
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
