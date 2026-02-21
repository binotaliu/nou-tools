<?php

namespace App\ViewModels;

use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class ScheduleViewModel
{
    /**
     * @param  Collection<int, \App\ViewModels\ScheduleMonthViewModel>  $months
     * @param  Collection<int, \App\ViewModels\ScheduleExamViewModel>  $exams
     */
    public function __construct(
        public StudentSchedule $schedule,
        public bool $hasAnyOverride,
        public Collection $months,
        public Collection $exams,
        public ScheduleCalendarUrlsViewModel $calendarUrls,
    ) {}

    public static function fromModel(StudentSchedule $schedule): self
    {
        return new self(
            schedule: $schedule,
            hasAnyOverride: self::checkHasAnyOverride($schedule),
            months: self::buildMonthsCollection($schedule),
            exams: self::buildExamsCollection($schedule),
            calendarUrls: ScheduleCalendarUrlsViewModel::fromModel($schedule),
        );
    }

    /**
     * Check if any course class schedule has a time override
     */
    private static function checkHasAnyOverride(StudentSchedule $schedule): bool
    {
        return $schedule->items->contains(function ($item) {
            return $item->courseClass->schedules->contains(function ($s) {
                return $s->start_time !== null;
            });
        });
    }

    /**
     * Group course schedules by month with formatted display data
     *
     * @return Collection<int, ScheduleMonthViewModel>
     */
    private static function buildMonthsCollection(StudentSchedule $schedule): Collection
    {
        $coursesByMonth = [];

        foreach ($schedule->items as $item) {
            foreach ($item->courseClass->schedules as $classSchedule) {
                $monthKey = $classSchedule->date->format('Y-m');
                $monthKeyDisplay = Carbon::parse($classSchedule->date)->isoFormat('Y 年 M 月');

                if (! isset($coursesByMonth[$monthKey])) {
                    $coursesByMonth[$monthKey] = [
                        'monthKey' => $monthKey,
                        'monthDisplay' => $monthKeyDisplay,
                        'dates' => [],
                    ];
                }

                $dateKey = $classSchedule->date->format('Y-m-d');
                if (! isset($coursesByMonth[$monthKey]['dates'][$dateKey])) {
                    $coursesByMonth[$monthKey]['dates'][$dateKey] = [
                        'date' => $classSchedule->date,
                        'dateKey' => $dateKey,
                        'courses' => [],
                    ];
                }

                // Use schedule time override if it exists, otherwise use class default
                $displayStartTime = $classSchedule->start_time ?? $item->courseClass->start_time;
                $displayEndTime = $classSchedule->end_time ?? $item->courseClass->end_time;
                $hasOverride = $classSchedule->start_time !== null;

                $coursesByMonth[$monthKey]['dates'][$dateKey]['courses'][] = new ScheduleCourseItemViewModel(
                    courseName: $item->courseClass->course->name,
                    code: $item->courseClass->code,
                    time: $displayStartTime ? $displayStartTime.' - '.$displayEndTime : '未設定',
                    hasOverride: $hasOverride,
                    date: $classSchedule->date,
                );
            }
        }

        return collect($coursesByMonth)
            ->sortKeys()
            ->map(function ($monthData) {
                $dates = collect($monthData['dates'])
                    ->sortKeys()
                    ->map(fn ($dateData) => new ScheduleDateViewModel(
                        date: $dateData['date'],
                        dateKey: $dateData['dateKey'],
                        courses: collect($dateData['courses']),
                    ))
                    ->values();

                return new ScheduleMonthViewModel(
                    monthKey: $monthData['monthKey'],
                    monthDisplay: $monthData['monthDisplay'],
                    dates: $dates,
                );
            })
            ->values();
    }

    /**
     * Get all courses with exam information, sorted by earliest exam date
     *
     * @return Collection<int, ScheduleExamViewModel>
     */
    private static function buildExamsCollection(StudentSchedule $schedule): Collection
    {
        $courses = $schedule->items
            ->map(fn ($it) => $it->courseClass->course)
            ->unique('id')
            ->values();

        $exams = $courses
            ->filter(fn ($c) => $c->midterm_date || $c->final_date || $c->exam_time_start || $c->exam_time_end)
            ->map(function ($course) use ($schedule) {
                $firstClass = $schedule->items->first(
                    fn ($it) => $it->courseClass->course->id === $course->id
                )?->courseClass;

                return ScheduleExamViewModel::fromCourse($course, $firstClass);
            })
            ->sortBy(fn ($exam) => $exam->earliestExamAt?->getTimestamp() ?? PHP_INT_MAX)
            ->values();

        return $exams;
    }
}
