<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Newsletter\Actions\FindViewableNewsletterIssue;
use NouTools\Domains\Newsletter\Actions\SetNewsletterReaction;
use NouTools\Domains\Newsletter\Actions\SummarizeNewsletterReactions;
use NouTools\Domains\Newsletter\DataTransferObjects\SetNewsletterReactionData;

/**
 * Only published issues take reactions, so an admin previewing a draft
 * can't skew the numbers.
 */
final class NewsletterReactionController extends Controller
{
    public function __invoke(
        string $issueKey,
        SetNewsletterReactionData $input,
        Request $request,
        FindViewableNewsletterIssue $findViewableNewsletterIssue,
        SetNewsletterReaction $setNewsletterReaction,
        SummarizeNewsletterReactions $summarizeNewsletterReactions,
    ): JsonResponse {
        $issue = $findViewableNewsletterIssue($issueKey);

        abort_if($issue === null, 404);

        $setNewsletterReaction($issue, $request->session(), $input);

        return response()->json([
            'reactions' => $summarizeNewsletterReactions($issue, $request->session()),
        ]);
    }
}
