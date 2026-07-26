<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\ScheduleCustomizationUpsertData;
use NouTools\Domains\Schedules\PageData\ScheduleCustomizationPageData;

final class UpdateScheduleCustomization
{
    public function __invoke(StudentSchedule $schedule, ScheduleCustomizationUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($schedule, $input) {
            $displayOptions = ScheduleCustomizationPageData::normalizeDisplayOptions($input->displayOptions)->toArray();

            $existingCalendarSettings = null;

            if (is_array($schedule->display_options) && array_key_exists('calendar_settings', $schedule->display_options)) {
                $existingCalendarSettings = ScheduleCustomizationPageData::normalizeCalendarSettings(
                    is_array($schedule->display_options['calendar_settings']) ? $schedule->display_options['calendar_settings'] : null,
                )->toArray();
            }

            if ($existingCalendarSettings !== null) {
                $displayOptions['calendar_settings'] = $existingCalendarSettings;
            }

            $schedule->display_options = $displayOptions;
            $schedule->custom_links = ScheduleCustomizationPageData::normalizeCustomLinks($input->customLinks)->toArray();
            $schedule->saveOrFail();

            return $schedule;
        });
    }
}
