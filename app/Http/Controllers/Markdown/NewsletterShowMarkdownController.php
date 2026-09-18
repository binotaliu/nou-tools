<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Enums\NewsletterSection;
use App\Http\Controllers\Controller;
use App\Models\NewsletterIssue;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use NouTools\Domains\Newsletter\Actions\FindViewableNewsletterIssue;

final class NewsletterShowMarkdownController extends Controller
{
    public function __invoke(string $issueKey, FindViewableNewsletterIssue $findViewableNewsletterIssue): Response
    {
        // The Markdown twin serves the raw editor Markdown rather than the
        // rendered HTML the Inertia page gets, so it reads the model directly.
        $issue = $findViewableNewsletterIssue($issueKey, Gate::allows('viewAny', NewsletterIssue::class));

        abort_if($issue === null, 404);

        return response()
            ->view('newsletter.markdown.show', [
                'issue' => $issue,
                'newsItems' => $issue->items->where('section', NewsletterSection::News)->values(),
                'centerItemsBySource' => $issue->items->where('section', NewsletterSection::Centers)->groupBy('source_name'),
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
