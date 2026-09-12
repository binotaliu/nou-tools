<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\StartNextRound;
use NouTools\Domains\StudyRoom\Exceptions\NoSeatHeldException;
use NouTools\Domains\StudyRoom\Exceptions\NotOnBreakException;

final class StudyRoomNextRoundController extends Controller
{
    public function __invoke(Request $request, StartNextRound $startNextRound, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        try {
            $startNextRound($viewer);
        } catch (NoSeatHeldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (NotOnBreakException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }
}
