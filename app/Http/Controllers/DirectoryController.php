<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use NouTools\Domains\Directory\Actions\ShowDirectoryIndexPage;

final class DirectoryController extends Controller
{
    public function index(ShowDirectoryIndexPage $showDirectoryIndexPage): View
    {
        return view('directory.index', [
            'viewModel' => $showDirectoryIndexPage(),
        ]);
    }
}
