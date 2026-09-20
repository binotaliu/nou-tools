<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\SetTimerEndNotification;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetTimerEndNotificationData;
use NouTools\Domains\StudyRoom\Exceptions\NoStudyRoomProfileException;

final class StudyRoomTimerEndNotificationController extends Controller
{
    public function __invoke(SetTimerEndNotificationData $input, Request $request, SetTimerEndNotification $setTimerEndNotification): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先建立課表。'], 403);
        }

        try {
            $setTimerEndNotification($viewer, $input);
        } catch (NoStudyRoomProfileException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }
}
