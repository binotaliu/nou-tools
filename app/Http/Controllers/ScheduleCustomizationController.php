<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Schedules\Actions\BuildAnnouncementPreferencesPage;
use NouTools\Domains\Schedules\Actions\BuildScheduleCustomizationPage;
use NouTools\Domains\Schedules\Actions\UpdateScheduleCustomization;
use NouTools\Domains\Schedules\DataTransferObjects\ScheduleCustomizationUpsertData;

final class ScheduleCustomizationController extends Controller
{
    public function edit(StudentSchedule $schedule, BuildScheduleCustomizationPage $buildScheduleCustomizationPage, BuildAnnouncementPreferencesPage $buildAnnouncementPreferencesPage): Response
    {
        return Inertia::render('Schedule/Customize', [
            'viewModel' => $buildScheduleCustomizationPage($schedule),
            'announcementPreferences' => $buildAnnouncementPreferencesPage($schedule),
        ]);
    }

    public function update(StudentSchedule $schedule, ScheduleCustomizationUpsertData $input, UpdateScheduleCustomization $updateScheduleCustomization): RedirectResponse
    {
        $updateScheduleCustomization($schedule, $input);

        return redirect()->route('schedules.show', $schedule)
            ->with('success', '課表自訂設定已更新！');
    }
}
