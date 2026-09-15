<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Schedules\Actions\BuildAnnouncementPreferencesPage;
use NouTools\Domains\Schedules\Actions\UpdateAnnouncementPreferences;
use NouTools\Domains\Schedules\DataTransferObjects\AnnouncementPreferencesUpsertData;

final class ScheduleAnnouncementPreferencesController extends Controller
{
    public function edit(StudentSchedule $schedule, BuildAnnouncementPreferencesPage $buildAnnouncementPreferencesPage): Response
    {
        return Inertia::render('Schedule/AnnouncementPreferences', [
            'viewModel' => $buildAnnouncementPreferencesPage($schedule),
        ]);
    }

    public function update(StudentSchedule $schedule, AnnouncementPreferencesUpsertData $input, UpdateAnnouncementPreferences $updateAnnouncementPreferences): RedirectResponse
    {
        $updateAnnouncementPreferences($schedule, $input);

        return redirect()->route('schedules.show', $schedule)
            ->with('success', '公告分類設定已更新！');
    }
}
