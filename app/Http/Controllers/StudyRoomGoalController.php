<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\SetStudyGoal;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetStudyGoalData;
use NouTools\Domains\StudyRoom\Exceptions\NoStudyRoomProfileException;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomGoalViewModel;

final class StudyRoomGoalController extends Controller
{
    public function __invoke(SetStudyGoalData $input, Request $request, SetStudyGoal $setStudyGoal): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先建立課表。'], 403);
        }

        try {
            $result = $setStudyGoal($viewer, $input);
        } catch (NoStudyRoomProfileException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'goal' => StudyRoomGoalViewModel::fromProfile($result)]);
    }
}
