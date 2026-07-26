<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

final class HomeIndexMarkdownController extends Controller
{
    public function __invoke(): Response
    {
        return response()
            ->view('llms-txt')
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
