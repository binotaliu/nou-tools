<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\UpdateScheduleCalendarSettings;
use NouTools\Domains\Schedules\DataTransferObjects\ScheduleCalendarSettingsUpsertData;
use NouTools\Domains\Schedules\PageData\ScheduleCustomizationPageData;

final class ScheduleCalendarSettingsUpdateController extends Controller
{
    public function __invoke(StudentSchedule $schedule, ScheduleCalendarSettingsUpsertData $input, Request $request, UpdateScheduleCalendarSettings $updateScheduleCalendarSettings): JsonResponse|RedirectResponse
    {
        $schedule = $updateScheduleCalendarSettings($schedule, $input);

        $calendarSettings = ScheduleCustomizationPageData::normalizeCalendarSettings(
            is_array($schedule->display_options['calendar_settings'] ?? null) ? $schedule->display_options['calendar_settings'] : null,
        );

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'calendar_settings' => $calendarSettings->toArray(),
            ]);
        }

        return redirect()
            ->route('schedules.subscribe', $schedule)
            ->with('success', '已儲存訂閱設定！');
    }
}
