<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\Newsletter\Actions\ShowNewsletterIndexPage;

final class NewsletterIndexMarkdownController extends Controller
{
    public function __invoke(ShowNewsletterIndexPage $showNewsletterIndexPage): Response
    {
        return response()
            ->view('newsletter.markdown.index', ['viewModel' => $showNewsletterIndexPage()])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
