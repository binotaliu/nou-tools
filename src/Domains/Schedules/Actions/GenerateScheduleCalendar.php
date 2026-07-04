<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use Carbon\Carbon;
use DateTimeInterface;
use NouTools\Domains\Schedules\ViewModels\ScheduleCustomizationPageViewModel;
use NouTools\Domains\SchoolCalendar\Actions\GetCurrentSchoolCalendar;

final readonly class GenerateScheduleCalendar
{
    public function __construct(
        private GetCurrentSchoolCalendar $getCurrentSchoolCalendar,
    ) {}

    public function __invoke(StudentSchedule $schedule): string
    {
        $schedule->load(['items.courseClass.schedules', 'items.courseClass.course']);

        $calendarSettings = ScheduleCustomizationPageViewModel::normalizeCalendarSettings(
            is_array($schedule->display_options['calendar_settings'] ?? null) ? $schedule->display_options['calendar_settings'] : null,
        );

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
                $lines[] = 'UID:course-'.$schedule->uuid.'-'.$item->id.'-'.$classSchedule->id.'@noutools.binota.org';
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

                if ($calendarSettings['class_reminders_enabled']) {
                    foreach ($calendarSettings['reminder_offsets'] as $offsetMinutes) {
                        $lines[] = 'BEGIN:VALARM';
                        $lines[] = 'TRIGGER:'.$this->convertMinutesToAlarmTrigger($offsetMinutes);
                        $lines[] = 'ACTION:DISPLAY';
                        $lines[] = 'DESCRIPTION:'.$this->escapeICSString('提醒: '.$course->name.' ('.$courseClass->code.')');
                        $lines[] = 'END:VALARM';
                    }
                }

                $lines[] = 'END:VEVENT';
            }
        }

        if ($calendarSettings['include_exams']) {
            $this->appendExamEvents($lines, $schedule);
        }

        if ($calendarSettings['include_school_calendar']) {
            $this->appendSchoolCalendarEvents($lines, $schedule);
        }

        $lines[] = 'END:VCALENDAR';

        StudentSchedule::withoutTimestamps(function () use ($schedule) {
            $schedule->forceFill(['last_calendar_sync_at' => now()])->save();
        });

        return implode("\r\n", $lines);
    }

    private function convertToICSDateTime(DateTimeInterface $date, string $time): string
    {
        $dateTime = Carbon::createFromFormat(
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

    /**
     * @param  array<int, string>  $lines
     */
    private function appendExamEvents(array &$lines, StudentSchedule $schedule): void
    {
        $courses = $schedule->items
            ->map(fn ($item) => $item->courseClass->course)
            ->unique('id')
            ->values();

        foreach ($courses as $course) {
            $firstClass = $schedule->items->first(
                fn ($item) => $item->courseClass->course->id === $course->id,
            )?->courseClass;

            $this->appendExamEvent($lines, $schedule, $course, $firstClass, 'midterm');
            $this->appendExamEvent($lines, $schedule, $course, $firstClass, 'final');
        }
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function appendExamEvent(array &$lines, StudentSchedule $schedule, Course $course, ?CourseClass $courseClass, string $examType): void
    {
        $date = $examType === 'midterm' ? $course->midterm_date : $course->final_date;

        if (! $date) {
            return;
        }

        $examLabel = $examType === 'midterm' ? '期中考' : '期末考';
        $hasExamTime = is_string($course->exam_time_start) && trim($course->exam_time_start) !== '';

        $descriptionParts = [];

        if ($courseClass?->code) {
            $descriptionParts[] = '班別: '.$courseClass->code;
        }

        if ($hasExamTime) {
            $descriptionParts[] = '考試時間: '.$this->formatExamTimeRange($course->exam_time_start, $course->exam_time_end);
        }

        $descriptionParts[] = '-----';
        $descriptionParts[] = '編輯課表: '.route('schedules.show', $schedule);

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:exam-'.$schedule->uuid.'-'.$course->id.'-'.$examType.'@noutools.binota.org';
        $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');

        if ($hasExamTime) {
            $startDateTime = Carbon::createFromFormat(
                'Y-m-d H:i',
                $date->format('Y-m-d').' '.substr((string) $course->exam_time_start, 0, 5),
                new \DateTimeZone('Asia/Taipei'),
            );

            $endDateTime = $course->exam_time_end
                ? Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $date->format('Y-m-d').' '.substr((string) $course->exam_time_end, 0, 5),
                    new \DateTimeZone('Asia/Taipei'),
                )
                : $startDateTime->copy()->addHour();

            $lines[] = 'DTSTART:'.$startDateTime->setTimezone('UTC')->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$endDateTime->setTimezone('UTC')->format('Ymd\THis\Z');
        } else {
            $start = Carbon::parse($date)->startOfDay();
            $end = $start->copy()->addDay();

            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$end->format('Ymd');
        }

        $lines[] = 'SUMMARY:'.$this->escapeICSString($course->name.' - '.$examLabel);
        $lines[] = 'DESCRIPTION:'.collect($descriptionParts)
            ->map(fn (string $part) => $this->escapeICSString($part))
            ->implode('\\n');
        $lines[] = 'END:VEVENT';
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function appendSchoolCalendarEvents(array &$lines, StudentSchedule $schedule): void
    {
        $events = ($this->getCurrentSchoolCalendar)();

        foreach ($events as $event) {
            $start = Carbon::parse($event->startDate)->startOfDay();
            $end = Carbon::parse($event->endDate)->startOfDay()->addDay();

            if ($end->lessThanOrEqualTo($start)) {
                $end = $start->copy()->addDay();
            }

            $uidHash = substr(md5($event->name.$event->startDate.$event->endDate), 0, 10);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:school-'.$schedule->uuid.'-'.$uidHash.'@noutools.binota.org';
            $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$end->format('Ymd');
            $lines[] = 'SUMMARY:'.$this->escapeICSString($event->name);
            $lines[] = 'DESCRIPTION:'.$this->escapeICSString('學校行事曆');
            $lines[] = 'END:VEVENT';
        }
    }

    private function convertMinutesToAlarmTrigger(int $minutes): string
    {
        if ($minutes % 1440 === 0) {
            return '-P'.((string) ($minutes / 1440)).'D';
        }

        if ($minutes % 60 === 0) {
            return '-PT'.((string) ($minutes / 60)).'H';
        }

        return '-PT'.$minutes.'M';
    }

    private function formatExamTimeRange(?string $examTimeStart, ?string $examTimeEnd): string
    {
        $start = is_string($examTimeStart) ? substr($examTimeStart, 0, 5) : null;
        $end = is_string($examTimeEnd) ? substr($examTimeEnd, 0, 5) : null;

        if ($start && $end) {
            return $start.' - '.$end;
        }

        return $start ?? $end ?? '未設定';
    }
}
