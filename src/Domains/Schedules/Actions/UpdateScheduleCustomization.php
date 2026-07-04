<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\ScheduleCustomizationUpsertData;
use NouTools\Domains\Schedules\ViewModels\ScheduleCustomizationPageViewModel;

final class UpdateScheduleCustomization
{
    public function __invoke(StudentSchedule $schedule, ScheduleCustomizationUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($schedule, $input) {
            $displayOptions = ScheduleCustomizationPageViewModel::normalizeDisplayOptions($input->displayOptions);

            $existingCalendarSettings = null;

            if (is_array($schedule->display_options) && array_key_exists('calendar_settings', $schedule->display_options)) {
                $existingCalendarSettings = ScheduleCustomizationPageViewModel::normalizeCalendarSettings(
                    is_array($schedule->display_options['calendar_settings']) ? $schedule->display_options['calendar_settings'] : null,
                );
            }

            if ($existingCalendarSettings !== null) {
                $displayOptions['calendar_settings'] = $existingCalendarSettings;
            }

            $schedule->display_options = $displayOptions;
            $schedule->custom_links = ScheduleCustomizationPageViewModel::normalizeCustomLinks($input->customLinks);
            $schedule->saveOrFail();

            return $schedule;
        });
    }
}
