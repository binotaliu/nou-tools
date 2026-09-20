<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\ResumeStudyTimer;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\Exceptions\TimerNotPausedException;

final class StudyRoomResumeController extends Controller
{
    public function __invoke(Request $request, ResumeStudyTimer $resumeStudyTimer, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        try {
            $resumeStudyTimer($viewer);
        } catch (NoSeatHeldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (TimerNotPausedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }
}
