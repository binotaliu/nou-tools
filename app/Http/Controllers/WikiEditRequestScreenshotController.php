<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WikiEditRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a screenshot of a request. The files sit on a private disk, and,
 * like the request's status page, anyone with the request's link may view
 * them.
 */
final class WikiEditRequestScreenshotController extends Controller
{
    public function __invoke(WikiEditRequest $editRequest, int $index): StreamedResponse
    {
        $screenshot = ($editRequest->screenshots ?? [])[$index] ?? null;

        abort_if($screenshot === null || ! Storage::disk(WikiEditRequest::SCREENSHOT_DISK)->exists($screenshot['path']), 404);

        return Storage::disk(WikiEditRequest::SCREENSHOT_DISK)->response($screenshot['path'], null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
