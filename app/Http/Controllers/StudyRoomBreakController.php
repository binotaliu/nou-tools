<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\StartBreak;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\Exceptions\TimerNotFinishedException;

final class StudyRoomBreakController extends Controller
{
    public function __invoke(Request $request, StartBreak $startBreak, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        try {
            $startBreak($viewer);
        } catch (NoSeatHeldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (TimerNotFinishedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }
}
