<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Directory\Actions\ShowDirectoryIndexPage;

final class DirectoryController extends Controller
{
    public function index(ShowDirectoryIndexPage $showDirectoryIndexPage): Response
    {
        return Inertia::render('Directory/Index', [
            'viewModel' => $showDirectoryIndexPage(),
            'mapTileLayer' => config('services.map.tileLayer'),
            'mapTileLayerAttribution' => config('services.map.tileLayerAttribution'),
        ]);
    }
}
