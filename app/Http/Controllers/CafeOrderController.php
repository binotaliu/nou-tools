<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Cafe\Actions\OrderCoffee;
use NouTools\Domains\Cafe\Actions\ResolveCafeViewer;
use NouTools\Domains\Cafe\DataTransferObjects\OrderCoffeeData;
use NouTools\Domains\Cafe\Exceptions\MenuItemUnavailableException;
use NouTools\Domains\Cafe\ViewModels\CafeCoffeeViewModel;

final class CafeOrderController extends Controller
{
    public function __invoke(OrderCoffeeData $input, Request $request, ResolveCafeViewer $resolveCafeViewer, OrderCoffee $orderCoffee, ReadAcornBalance $readAcornBalance): JsonResponse
    {
        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先到自習室設定暱稱，才能點餐。'], 403);
        }

        try {
            $order = $orderCoffee($viewer, $input);
        } catch (InsufficientAcornsException|MenuItemUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'coffee' => CafeCoffeeViewModel::fromOrder($order),
            'acorns' => $readAcornBalance($viewer->id),
        ]);
    }
}
