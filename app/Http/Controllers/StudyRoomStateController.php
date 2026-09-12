<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;

final class StudyRoomStateController extends Controller
{
    public function __invoke(Request $request, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewModel = $buildStudyRoomState($request->studentScheduleFromCookie());

        $etag = '"'.$viewModel->version.'"';

        // The client does its own conditional request (If-None-Match with
        // the version it holds) and handles the 304 itself. The browser's
        // HTTP cache must stay out of it: left cacheable, a 200 body gets
        // kept and, once the browser starts revalidating with the cached
        // ETag, a later 304 is handed to the page as that stale body —
        // the room silently snaps back to an older state.
        $headers = ['ETag' => $etag, 'Cache-Control' => 'no-store'];

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->withHeaders($headers);
        }

        return response()->json($viewModel)->withHeaders($headers);
    }
}
