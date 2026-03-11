<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;

final class GenerateScheduleCalendar
{
    public function __invoke(StudentSchedule $schedule): string
    {
        $schedule->load(['items.courseClass.schedules', 'items.courseClass.course']);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nou Tools//Schedule//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:'.$this->escapeICSString($schedule->name ?? '我的課表'),
            'METHOD:PUBLISH',
        ];

        foreach ($schedule->items as $item) {
            $courseClass = $item->courseClass;
            $course = $courseClass->course;

            foreach ($courseClass->schedules as $classSchedule) {
                $startTime = $courseClass->start_time ? substr($courseClass->start_time, 0, 5) : '09:00';
                $endTime = $courseClass->end_time ? substr($courseClass->end_time, 0, 5) : '10:00';
                $descriptionParts = [];

                if ($courseClass->teacher_name) {
                    $descriptionParts[] = '老師: '.$courseClass->teacher_name;
                }

                if ($courseClass->link) {
                    $descriptionParts[] = '視訊連結: '.$courseClass->link;
                }

                $descriptionParts[] = '-----';
                $descriptionParts[] = '編輯課表: '.route('schedules.show', $schedule);

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:'.$schedule->uuid.'-'.$item->id.'-'.$classSchedule->id.'@noutools.binota.org';
                $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
                $lines[] = 'DTSTART:'.$this->convertToICSDateTime($classSchedule->date, $startTime);
                $lines[] = 'DTEND:'.$this->convertToICSDateTime($classSchedule->date, $endTime);
                $lines[] = 'SUMMARY:'.$this->escapeICSString($course->name.' ('.$courseClass->code.')');
                $lines[] = 'DESCRIPTION:'.collect($descriptionParts)
                    ->map(fn (string $part) => $this->escapeICSString($part))
                    ->implode('\n');

                if ($courseClass->link) {
                    $lines[] = 'URL:'.$courseClass->link;
                }

                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        StudentSchedule::withoutTimestamps(function () use ($schedule) {
            $schedule->forceFill(['last_calendar_sync_at' => now()])->save();
        });

        return implode("\r\n", $lines);
    }

    private function convertToICSDateTime(\DateTimeInterface $date, string $time): string
    {
        $dateTime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            $date->format('Y-m-d').' '.substr($time, 0, 5),
            new \DateTimeZone('Asia/Taipei'),
        )->setTimezone('UTC');

        return $dateTime->format('Ymd\THis\Z');
    }

    private function escapeICSString(string $value): string
    {
        return str_replace(["\r\n", "\n", ',', ';', '\\'], ['\\n', '\\n', '\\,', '\\;', '\\\\'], $value);
    }
}
