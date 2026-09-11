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

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->header('ETag', $etag);
        }

        return response()->json($viewModel)->header('ETag', $etag);
    }
}
