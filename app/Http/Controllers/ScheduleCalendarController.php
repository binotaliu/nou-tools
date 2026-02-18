<?php

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\Response;

class ScheduleCalendarController extends Controller
{
    /**
     * Return an .ics calendar export for a saved student schedule.
     */
    public function __invoke(StudentSchedule $schedule): Response
    {
        $schedule->load(['items.courseClass.schedules']);

        $ics = $this->generateICS($schedule);

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="schedule.ics"');
    }

    private function generateICS(StudentSchedule $schedule): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nou Tools//Schedule//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($schedule->items as $item) {
            $courseClass = $item->courseClass;
            $course = $courseClass->course;

            foreach ($courseClass->schedules as $classSchedule) {
                $dateStr = $classSchedule->date->format('Ymd');
                $startTime = $courseClass->start_time ? substr($courseClass->start_time, 0, 5) : '09:00';
                $endTime = $courseClass->end_time ? substr($courseClass->end_time, 0, 5) : '10:00';

                $startDateTime = $this->convertToICSDateTime($classSchedule->date, $startTime);
                $endDateTime = $this->convertToICSDateTime($classSchedule->date, $endTime);

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:'.$schedule->uuid.'-'.$item->id.'-'.$classSchedule->id.'@noutools.binota.org';
                $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
                $lines[] = 'DTSTART:'.$startDateTime;
                $lines[] = 'DTEND:'.$endDateTime;
                $lines[] = 'SUMMARY:'.$this->escapeICSString($course->name.' ('.$courseClass->code.')');

                $descriptionParts = [];
                if ($courseClass->teacher_name) {
                    $descriptionParts[] = '老師: '.$courseClass->teacher_name;
                }
                $descriptionParts[] = '編輯課表: '.route('schedule.show', $schedule);
                $lines[] = 'DESCRIPTION:'.collect($descriptionParts)
                    ->map($this->escapeICSString(...))
                    ->implode('\n');

                if ($courseClass->link) {
                    $lines[] = 'URL:'.$courseClass->link;
                }

                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    private function convertToICSDateTime(\DateTime $date, string $time): string
    {
        $timeParts = explode(':', $time);
        $hour = $timeParts[0] ?? '09';
        $minute = $timeParts[1] ?? '00';

        return $date->format('Ymd').'T'.str_pad($hour, 2, '0', STR_PAD_LEFT).str_pad($minute, 2, '0', STR_PAD_LEFT).'00Z';
    }

    private function escapeICSString(string $string): string
    {
        return str_replace(["\r\n", "\n", ',', ';', '\\'], ['\\n', '\\n', '\\,', '\\;', '\\\\'], $string);
    }
}
