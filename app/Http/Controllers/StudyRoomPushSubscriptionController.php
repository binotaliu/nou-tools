<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\SubscribeScheduleToPush;
use NouTools\Domains\Schedules\DataTransferObjects\PushSubscriptionUpsertData;

/**
 * Registers the browser's push subscription from inside 自習室, where the
 * viewer is identified by the `student_schedule` cookie rather than by a
 * route parameter as on the schedule page.
 *
 * There is no DELETE counterpart on purpose. A browser holds a single push
 * subscription that the class-starting reminders share, so unsubscribing
 * here would silently switch those off too; turning study-room
 * notifications back off only clears `notify_on_timer_end` on the profile.
 */
final class StudyRoomPushSubscriptionController extends Controller
{
    public function __invoke(Request $request, PushSubscriptionUpsertData $input, SubscribeScheduleToPush $subscribeScheduleToPush): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先設定暱稱後再使用自習室。'], 403);
        }

        $schedule = StudentSchedule::query()->findOrFail($viewer->id);

        $subscribeScheduleToPush($schedule, $input);

        return response()->json(['ok' => true]);
    }
}
