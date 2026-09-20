<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use NouTools\Domains\Schedules\Actions\EnableClassStartingReminders;
use NouTools\Domains\Schedules\DataTransferObjects\PushSubscriptionUpsertData;

final class SchedulePushSubscriptionStoreController extends Controller
{
    public function __invoke(StudentSchedule $schedule, PushSubscriptionUpsertData $input, EnableClassStartingReminders $enableClassStartingReminders): JsonResponse
    {
        $enableClassStartingReminders($schedule, $input);

        return response()->json(['success' => true]);
    }
}
