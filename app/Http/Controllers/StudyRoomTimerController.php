<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\StartStudyTimer;
use NouTools\Domains\StudyRoom\Actions\StopStudyTimer;
use NouTools\Domains\StudyRoom\DataTransferObjects\StartStudyTimerData;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;

final class StudyRoomTimerController extends Controller
{
    public function store(StartStudyTimerData $input, Request $request, StartStudyTimer $startStudyTimer, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        try {
            $startStudyTimer($viewer, $input);
        } catch (NoSeatHeldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }

    public function destroy(Request $request, StopStudyTimer $stopStudyTimer, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        try {
            $stopStudyTimer($viewer);
        } catch (NoSeatHeldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }
}
