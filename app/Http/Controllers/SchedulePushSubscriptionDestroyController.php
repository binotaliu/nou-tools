<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use NouTools\Domains\Schedules\Actions\DisableClassStartingReminders;

final class SchedulePushSubscriptionDestroyController extends Controller
{
    public function __invoke(StudentSchedule $schedule, DisableClassStartingReminders $disableClassStartingReminders): JsonResponse
    {
        $disableClassStartingReminders($schedule);

        return response()->json(['success' => true]);
    }
}
