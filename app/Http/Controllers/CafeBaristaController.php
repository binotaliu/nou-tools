<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\Actions\TalkToBarista;

final class CafeBaristaController extends Controller
{
    /**
     * `?drink=` asks for the line that follows an order instead of a greeting.
     */
    public function __invoke(Request $request, TalkToBarista $talkToBarista): JsonResponse
    {
        $drink = $request->string('drink')->limit(32, '')->toString();

        return response()->json([
            'line' => $talkToBarista($request, $drink === '' ? null : $drink),
        ]);
    }
}
