<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\Directory\Actions\ShowDirectoryIndexPage;

final class DirectoryIndexMarkdownController extends Controller
{
    public function __invoke(ShowDirectoryIndexPage $showDirectoryIndexPage): Response
    {
        return response()
            ->view('directory.markdown.index', ['viewModel' => $showDirectoryIndexPage()])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
