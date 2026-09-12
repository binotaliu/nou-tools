<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\ListStudyRoomSessions;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSessionListViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSessionViewModel;
use Spatie\LaravelData\DataCollection;

final class StudyRoomSessionController extends Controller
{
    public function __invoke(Request $request, ListStudyRoomSessions $listStudyRoomSessions): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        $viewModel = new StudyRoomSessionListViewModel(
            sessions: StudyRoomSessionViewModel::collect($listStudyRoomSessions($viewer), DataCollection::class),
        );

        return response()->json($viewModel);
    }
}
