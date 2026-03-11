<?php

namespace NouTools\Domains\Schedules\ViewModels;

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

        return new self(
            ics: $icsUrl,
            webcal: $webcalUrl,
            google: 'https://calendar.google.com/calendar/r?cid='.urlencode($webcalUrl),
            outlook: 'https://outlook.office.com/calendar/0/addfromweb?url='.urlencode($webcalUrl),
        );
    }
}
