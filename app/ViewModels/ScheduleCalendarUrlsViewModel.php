<?php

namespace App\ViewModels;

use App\Models\StudentSchedule;
use Spatie\LaravelData\Data;

final class ScheduleCalendarUrlsViewModel extends Data
{
    public function __construct(
        public string $ics,
        public string $webcal,
        public string $google,
        public string $outlook,
    ) {}

    public static function fromModel(StudentSchedule $schedule): self
    {
        $icsUrl = route('schedules.calendar', $schedule);
        $webcalUrl = preg_replace('/^https?/', 'webcal', $icsUrl);
        $googleUrl = 'https://calendar.google.com/calendar/r?cid='.urlencode($webcalUrl);
        $outlookWebUrl = 'https://outlook.office.com/calendar/0/addfromweb?url='.urlencode($webcalUrl);

        return new self(
            ics: $icsUrl,
            webcal: $webcalUrl,
            google: $googleUrl,
            outlook: $outlookWebUrl,
        );
    }
}
