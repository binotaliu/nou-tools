<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudyRoomSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\Actions\BuildCafeState;
use NouTools\Domains\Cafe\Actions\LeaveCafeSeat;
use NouTools\Domains\Cafe\Actions\ResolveCafeViewer;
use NouTools\Domains\Cafe\Actions\TakeCafeSeat;
use NouTools\Domains\Cafe\Exceptions\CafeAlreadySeatedException;
use NouTools\Domains\Cafe\Exceptions\CafeSeatUnavailableException;

final class CafeSeatController extends Controller
{
    public function store(Request $request, StudyRoomSeat $seat, ResolveCafeViewer $resolveCafeViewer, TakeCafeSeat $takeCafeSeat, BuildCafeState $buildCafeState): JsonResponse
    {
        // `{seat}` binds any seat by code; only café seats are takeable here.
        abort_unless($seat->isCafe(), 404);

        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先至自習室設定暱稱後方可入座。'], 403);
        }

        try {
            $takeCafeSeat($viewer, $seat);
        } catch (CafeAlreadySeatedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (CafeSeatUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'state' => $buildCafeState($viewer)]);
    }

    public function destroy(Request $request, ResolveCafeViewer $resolveCafeViewer, LeaveCafeSeat $leaveCafeSeat, BuildCafeState $buildCafeState): JsonResponse
    {
        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先至自習室設定暱稱。'], 403);
        }

        $leaveCafeSeat($viewer);

        return response()->json(['ok' => true, 'state' => $buildCafeState($viewer)]);
    }
}
