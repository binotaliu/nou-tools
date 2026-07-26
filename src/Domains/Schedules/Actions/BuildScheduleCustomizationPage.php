<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\PageData\ScheduleCustomizationPageData;

final class BuildScheduleCustomizationPage
{
    public function __invoke(StudentSchedule $schedule): ScheduleCustomizationPageData
    {
        return new ScheduleCustomizationPageData(
            schedule: $schedule,
            displayOptions: ScheduleCustomizationPageData::normalizeDisplayOptions($schedule->display_options),
            customLinks: ScheduleCustomizationPageData::normalizeCustomLinks($schedule->custom_links),
        );
    }
}
