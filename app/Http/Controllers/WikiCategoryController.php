<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WikiCategory;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\ShowWikiCategoryPage;

final class WikiCategoryController extends Controller
{
    public function __invoke(WikiCategory $category, ShowWikiCategoryPage $showWikiCategoryPage): Response
    {
        return Inertia::render('Wiki/Category', [
            'viewModel' => $showWikiCategoryPage($category),
        ]);
    }
}
