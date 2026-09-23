<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use NouTools\Domains\VideoClasses\Actions\ShowVideoClassesPage;
use NouTools\Domains\VideoClasses\DataTransferObjects\ShowVideoClassesData;
use Symfony\Component\HttpFoundation\Response;

final class VideoClassController extends Controller
{
    public function index(ShowVideoClassesPage $showVideoClassesPage, ShowVideoClassesData $input): Response
    {
        return Inertia::render('VideoClasses/Index', [
            'viewModel' => $showVideoClassesPage($input),
        ])->toResponse(request());
    }
}
