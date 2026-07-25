<?php

namespace NouTools\Domains\Schedules\PageData;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ViewModels\ScheduleCalendarSettingsViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleCalendarUrlsViewModel;
use Spatie\LaravelData\Resource;

final class ScheduleSubscribePageData extends Resource
{
    public function __construct(
        public string $uuid,
        public ?string $name,
        public ScheduleCalendarSettingsViewModel $calendarSettings,
        public ScheduleCalendarUrlsViewModel $calendarUrls,
    ) {}

    public static function fromModel(StudentSchedule $schedule): self
    {
        return new self(
            uuid: $schedule->getRouteKey(),
            name: $schedule->name,
            calendarSettings: ScheduleCustomizationPageData::normalizeCalendarSettings(
                is_array($schedule->display_options['calendar_settings'] ?? null) ? $schedule->display_options['calendar_settings'] : null,
            ),
            calendarUrls: ScheduleCalendarUrlsViewModel::fromModel($schedule),
        );
    }
}
