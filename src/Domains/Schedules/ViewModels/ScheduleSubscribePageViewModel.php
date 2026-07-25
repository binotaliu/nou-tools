<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentSchedule;
use Spatie\LaravelData\Data;

final class ScheduleSubscribePageViewModel extends Data
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
            calendarSettings: ScheduleCustomizationPageViewModel::normalizeCalendarSettings(
                is_array($schedule->display_options['calendar_settings'] ?? null) ? $schedule->display_options['calendar_settings'] : null,
            ),
            calendarUrls: ScheduleCalendarUrlsViewModel::fromModel($schedule),
        );
    }
}
