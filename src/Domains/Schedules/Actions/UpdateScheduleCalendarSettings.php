<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\ScheduleCalendarSettingsUpsertData;
use NouTools\Domains\Schedules\ViewModels\ScheduleCalendarSettingsViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleCustomizationPageViewModel;

final class UpdateScheduleCalendarSettings
{
    public function __invoke(StudentSchedule $schedule, ScheduleCalendarSettingsUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($schedule, $input) {
            $displayOptions = ScheduleCustomizationPageViewModel::normalizeDisplayOptions(
                is_array($schedule->display_options) ? $schedule->display_options : null,
            )->toArray();

            $calendarSettings = ScheduleCustomizationPageViewModel::normalizeCalendarSettings([
                'include_school_calendar' => $input->includeSchoolCalendar,
                'include_exams' => $input->includeExams,
                'class_reminders_enabled' => $input->classRemindersEnabled,
                'reminder_offsets' => $input->reminderOffsets,
            ]);

            if (! $calendarSettings->classRemindersEnabled) {
                $calendarSettings = new ScheduleCalendarSettingsViewModel(
                    includeSchoolCalendar: $calendarSettings->includeSchoolCalendar,
                    includeExams: $calendarSettings->includeExams,
                    classRemindersEnabled: $calendarSettings->classRemindersEnabled,
                    reminderOffsets: ScheduleCustomizationPageViewModel::defaultCalendarSettings()->reminderOffsets,
                );
            }

            $displayOptions['calendar_settings'] = $calendarSettings->toArray();

            $schedule->display_options = $displayOptions;
            $schedule->saveOrFail();

            return $schedule;
        });
    }
}
