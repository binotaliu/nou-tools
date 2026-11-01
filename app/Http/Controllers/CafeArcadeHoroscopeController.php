<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Arcade\Actions\PlayHoroscope;
use NouTools\Domains\Arcade\Actions\ShowHoroscopeMachine;
use NouTools\Domains\Arcade\DataTransferObjects\PlayHoroscopeData;
use NouTools\Domains\Arcade\ViewModels\HoroscopeReadingViewModel;
use NouTools\Domains\Cafe\Actions\ResolveCafeViewer;

final class CafeArcadeHoroscopeController extends Controller
{
    public function show(Request $request, ShowHoroscopeMachine $showHoroscopeMachine): JsonResponse
    {
        return response()->json([
            'machine' => $showHoroscopeMachine($request->studentScheduleFromCookie()),
        ]);
    }

    public function store(PlayHoroscopeData $input, Request $request, ResolveCafeViewer $resolveCafeViewer, PlayHoroscope $playHoroscope, ReadAcornBalance $readAcornBalance): JsonResponse
    {
        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先至自習室設定暱稱後方可使用遊樂機台。'], 403);
        }

        try {
            $play = $playHoroscope($viewer, $input->sign);
        } catch (InsufficientAcornsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'reading' => HoroscopeReadingViewModel::fromPlay($play),
            'acorns' => $readAcornBalance($viewer->id),
        ]);
    }
}
