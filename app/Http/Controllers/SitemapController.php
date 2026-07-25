<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use NouTools\Domains\Sitemap\Actions\GenerateSitemap;

class SitemapController extends Controller
{
    public function index(GenerateSitemap $generateSitemap): Response
    {
        $urls = $generateSitemap();

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
