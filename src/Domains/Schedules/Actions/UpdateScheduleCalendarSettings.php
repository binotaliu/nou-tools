<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\ScheduleCalendarSettingsUpsertData;
use NouTools\Domains\Schedules\ViewModels\ScheduleCustomizationPageViewModel;

final class UpdateScheduleCalendarSettings
{
    public function __invoke(StudentSchedule $schedule, ScheduleCalendarSettingsUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($schedule, $input) {
            $displayOptions = ScheduleCustomizationPageViewModel::normalizeDisplayOptions(
                is_array($schedule->display_options) ? $schedule->display_options : null,
            );

            $calendarSettings = ScheduleCustomizationPageViewModel::normalizeCalendarSettings([
                'include_school_calendar' => $input->includeSchoolCalendar,
                'include_exams' => $input->includeExams,
                'class_reminders_enabled' => $input->classRemindersEnabled,
                'reminder_offsets' => $input->reminderOffsets,
            ]);

            if (! $calendarSettings['class_reminders_enabled']) {
                $calendarSettings['reminder_offsets'] = ScheduleCustomizationPageViewModel::defaultCalendarSettings()['reminder_offsets'];
            }

            $displayOptions['calendar_settings'] = $calendarSettings;

            $schedule->display_options = $displayOptions;
            $schedule->saveOrFail();

            return $schedule;
        });
    }
}
