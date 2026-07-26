<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use NouTools\Domains\Shared\Sitemap\Actions\GenerateSitemap;

final class SitemapController extends Controller
{
    public function index(GenerateSitemap $generateSitemap): Response
    {
        $urls = $generateSitemap();

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
