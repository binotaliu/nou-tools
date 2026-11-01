<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\Actions\PutAwayCoffee;
use NouTools\Domains\Cafe\Actions\ResolveCafeViewer;
use NouTools\Domains\Cafe\Exceptions\NoCoffeeToHandleException;

/**
 * Puts the cup on the student's table away.
 */
final class CafeCoffeePutAwayController extends Controller
{
    public function __invoke(Request $request, ResolveCafeViewer $resolveCafeViewer, PutAwayCoffee $putAwayCoffee): JsonResponse
    {
        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先至自習室設定暱稱。'], 403);
        }

        try {
            $putAwayCoffee($viewer);
        } catch (NoCoffeeToHandleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }
}
