<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\LeaveSeat;
use NouTools\Domains\StudyRoom\Actions\TakeSeat;
use NouTools\Domains\StudyRoom\Exceptions\AlreadySeatedException;
use NouTools\Domains\StudyRoom\Exceptions\FloorClosedException;
use NouTools\Domains\StudyRoom\Exceptions\SeatUnavailableException;

final class StudyRoomSeatController extends Controller
{
    public function store(Request $request, StudyRoomSeat $seat, TakeSeat $takeSeat, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $this->requireProfiledViewer($request);

        if ($viewer instanceof JsonResponse) {
            return $viewer;
        }

        try {
            $takeSeat($viewer, $seat);
        } catch (FloorClosedException|AlreadySeatedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (SeatUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }

    public function destroy(Request $request, LeaveSeat $leaveSeat, BuildStudyRoomState $buildStudyRoomState): JsonResponse
    {
        $viewer = $this->requireProfiledViewer($request);

        if ($viewer instanceof JsonResponse) {
            return $viewer;
        }

        $leaveSeat($viewer);

        return response()->json(['ok' => true, 'state' => $buildStudyRoomState($viewer)]);
    }

    /**
     * Resolves the caller's schedule cookie and confirms they've already
     * set a 自習室 nickname, returning a ready-to-use error response
     * otherwise (no cookie, or no profile yet).
     */
    private function requireProfiledViewer(Request $request): StudentScheduleCookie|JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        $hasProfile = StudyRoomProfile::query()
            ->where('student_schedule_id', $viewer->id)
            ->whereNotNull('nickname')
            ->exists();

        if (! $hasProfile) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        return $viewer;
    }
}
