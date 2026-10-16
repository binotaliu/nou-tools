<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\Wiki\Actions\ShowWikiAllPagesPage;

final class WikiIndexMarkdownController extends Controller
{
    public function __invoke(ShowWikiAllPagesPage $showWikiAllPagesPage): Response
    {
        return response()
            ->view('wiki.markdown.index', [
                'viewModel' => $showWikiAllPagesPage(),
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
