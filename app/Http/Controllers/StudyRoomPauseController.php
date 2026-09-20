<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\PauseStudyTimer;
use NouTools\Domains\StudyRoom\Exceptions\CannotPauseTimerException;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;

final class StudyRoomPauseController extends Controller
{
    public function __invoke(Request $request, PauseStudyTimer $pauseStudyTimer, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        try {
            $pauseStudyTimer($viewer);
        } catch (NoSeatHeldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (CannotPauseTimerException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }
}
