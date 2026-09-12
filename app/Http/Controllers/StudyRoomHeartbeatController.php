<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\RecordHeartbeat;

final class StudyRoomHeartbeatController extends Controller
{
    public function __invoke(Request $request, RecordHeartbeat $recordHeartbeat): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        $stillSeated = $recordHeartbeat($viewer);

        return response()->json(['ok' => true, 'stillSeated' => $stillSeated]);
    }
}
