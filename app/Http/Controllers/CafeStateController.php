<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\Actions\BuildCafeState;

final class CafeStateController extends Controller
{
    public function __invoke(Request $request, BuildCafeState $buildCafeState): JsonResponse
    {
        $viewModel = $buildCafeState($request->studentScheduleFromCookie());
        $etag = '"'.$viewModel->version.'"';

        // Same contract as the study room: the client revalidates with its own
        // If-None-Match, so the HTTP cache must stay out of it.
        $headers = ['ETag' => $etag, 'Cache-Control' => 'no-store'];

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->withHeaders($headers);
        }

        return response()->json($viewModel)->withHeaders($headers);
    }
}
