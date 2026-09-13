<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\ListRecentStudyRoomSessionsForStats;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSessionStatsViewModel;

final class StudyRoomSessionStatsController extends Controller
{
    public function __invoke(Request $request, ListRecentStudyRoomSessionsForStats $listRecentStudyRoomSessionsForStats): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        $viewModel = StudyRoomSessionStatsViewModel::fromSessions(
            $listRecentStudyRoomSessionsForStats($viewer),
            (string) config('app.schedule_timezone'),
        );

        return response()->json($viewModel);
    }
}
