<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;

final class AcornController extends Controller
{
    public function __invoke(Request $request, ReadAcornBalance $readAcornBalance): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先建立課表。'], 403);
        }

        return response()->json(['acorns' => $readAcornBalance($viewer->id)]);
    }
}
