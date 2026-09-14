<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use NouTools\Domains\Schedules\Actions\UnsubscribeScheduleFromPush;
use NouTools\Domains\Schedules\DataTransferObjects\PushUnsubscribeData;

final class SchedulePushSubscriptionDestroyController extends Controller
{
    public function __invoke(StudentSchedule $schedule, PushUnsubscribeData $input, UnsubscribeScheduleFromPush $unsubscribeScheduleFromPush): JsonResponse
    {
        $unsubscribeScheduleFromPush($schedule, $input);

        return response()->json(['success' => true]);
    }
}
