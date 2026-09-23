<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\VideoClasses\Actions\ShowVideoClassesPage;
use NouTools\Domains\VideoClasses\DataTransferObjects\ShowVideoClassesData;

final class VideoClassIndexMarkdownController extends Controller
{
    public function __invoke(ShowVideoClassesPage $showVideoClassesPage, ShowVideoClassesData $input): Response
    {
        return response()
            ->view('video-classes.markdown.index', ['viewModel' => $showVideoClassesPage($input)])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
