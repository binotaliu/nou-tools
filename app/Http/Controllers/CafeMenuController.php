<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NouTools\Domains\Cafe\Actions\ListCafeMenu;

final class CafeMenuController extends Controller
{
    public function __invoke(ListCafeMenu $listCafeMenu): JsonResponse
    {
        return response()->json(['menu' => $listCafeMenu()]);
    }
}
