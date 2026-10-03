<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NouTools\Domains\Schedules\Actions\RestoreScheduleDevice;
use NouTools\Domains\Schedules\DataTransferObjects\RestoreScheduleDeviceData;

final class ScheduleDeviceRestoreController extends Controller
{
    public function __invoke(RestoreScheduleDeviceData $input, RestoreScheduleDevice $restoreScheduleDevice): JsonResponse
    {
        $cookie = $restoreScheduleDevice($input);

        if ($cookie === null) {
            return response()->json(['restored' => false], 422);
        }

        return response()->json(['restored' => true])->cookie($cookie);
    }
}
