<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\SetGoalReminder;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetGoalReminderData;
use NouTools\Domains\StudyRoom\Exceptions\NoStudyRoomProfileException;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomGoalViewModel;

final class StudyRoomGoalReminderController extends Controller
{
    public function __invoke(SetGoalReminderData $input, Request $request, SetGoalReminder $setGoalReminder): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先建立課表。'], 403);
        }

        try {
            $result = $setGoalReminder($viewer, $input);
        } catch (NoStudyRoomProfileException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'goal' => StudyRoomGoalViewModel::fromProfile($result)]);
    }
}
