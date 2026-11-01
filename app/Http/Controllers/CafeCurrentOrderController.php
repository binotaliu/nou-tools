<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\Actions\PutAwayCoffee;
use NouTools\Domains\Cafe\Actions\ResolveCafeViewer;
use NouTools\Domains\Cafe\Actions\SipCoffee;
use NouTools\Domains\Cafe\Exceptions\NoCoffeeToHandleException;
use NouTools\Domains\Cafe\ViewModels\CafeCoffeeViewModel;

/**
 * What a student does with the cup on their table: take a sip, or put it away.
 */
final class CafeCurrentOrderController extends Controller
{
    public function sip(Request $request, ResolveCafeViewer $resolveCafeViewer, SipCoffee $sipCoffee): JsonResponse
    {
        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先至自習室設定暱稱。'], 403);
        }

        try {
            $order = $sipCoffee($viewer);
        } catch (NoCoffeeToHandleException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'coffee' => CafeCoffeeViewModel::fromOrder($order)]);
    }

    public function destroy(Request $request, ResolveCafeViewer $resolveCafeViewer, PutAwayCoffee $putAwayCoffee): JsonResponse
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
