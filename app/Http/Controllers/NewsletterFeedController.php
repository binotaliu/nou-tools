<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use NouTools\Domains\Newsletter\Actions\ListNewsletterFeedIssues;

final class NewsletterFeedController extends Controller
{
    public function __invoke(ListNewsletterFeedIssues $listNewsletterFeedIssues): Response
    {
        return response()
            ->view('newsletter.feed', [
                'title' => config('newsletter.title'),
                'issues' => $listNewsletterFeedIssues(),
            ])
            ->header('Content-Type', 'application/atom+xml; charset=utf-8');
    }
}
