<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ScheduleViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public ?string $name,
        #[DataCollectionOf(StudentScheduleItemViewModel::class)]
        public DataCollection $items,
        public bool $hasAnyOverride,
        #[DataCollectionOf(ScheduleMonthViewModel::class)]
        public DataCollection $months,
        #[DataCollectionOf(ScheduleExamViewModel::class)]
        public DataCollection $exams,
        public ScheduleCalendarUrlsViewModel $calendarUrls,
    ) {}

    public static function fromModel(StudentSchedule $schedule): self
    {
        return new self(
            id: $schedule->id,
            uuid: $schedule->getRouteKey(),
            name: $schedule->name,
            items: StudentScheduleItemViewModel::collect(
                $schedule->items->map(fn ($item) => StudentScheduleItemViewModel::fromModel($item)),
                DataCollection::class,
            ),
            hasAnyOverride: self::hasAnyOverride($schedule),
            months: self::buildMonths($schedule),
            exams: self::buildExams($schedule),
            calendarUrls: ScheduleCalendarUrlsViewModel::fromModel($schedule),
        );
    }

    private static function hasAnyOverride(StudentSchedule $schedule): bool
    {
        return $schedule->items->contains(function ($item) {
            return $item->courseClass->schedules->contains(function ($schedule) {
                return $schedule->start_time !== null;
            });
        });
    }

    private static function buildMonths(StudentSchedule $schedule): DataCollection
    {
        $coursesByMonth = [];

        foreach ($schedule->items as $item) {
            foreach ($item->courseClass->schedules as $classSchedule) {
                $monthKey = $classSchedule->date->format('Y-m');
                $monthDisplay = Carbon::parse($classSchedule->date)->isoFormat('Y 年 M 月');

                if (! isset($coursesByMonth[$monthKey])) {
                    $coursesByMonth[$monthKey] = [
                        'monthKey' => $monthKey,
                        'monthDisplay' => $monthDisplay,
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

                $displayStartTime = $classSchedule->start_time ?? $item->courseClass->start_time;
                $displayEndTime = $classSchedule->end_time ?? $item->courseClass->end_time;

                $coursesByMonth[$monthKey]['dates'][$dateKey]['courses'][] = new ScheduleCourseItemViewModel(
                    courseName: $item->courseClass->course->name,
                    code: $item->courseClass->code,
                    time: $displayStartTime ? $displayStartTime.' - '.$displayEndTime : '未設定',
                    hasOverride: $classSchedule->start_time !== null,
                    date: $classSchedule->date,
                );
            }
        }

        $months = collect($coursesByMonth)
            ->sortKeys()
            ->map(function (array $monthData) {
                $dates = ScheduleDateViewModel::collect(
                    collect($monthData['dates'])
                        ->sortKeys()
                        ->map(fn (array $dateData) => [
                            'date' => $dateData['date'],
                            'dateKey' => $dateData['dateKey'],
                            'courses' => ScheduleCourseItemViewModel::collect($dateData['courses'], DataCollection::class),
                        ])
                        ->values(),
                    DataCollection::class,
                );

                return new ScheduleMonthViewModel(
                    monthKey: $monthData['monthKey'],
                    monthDisplay: $monthData['monthDisplay'],
                    dates: $dates,
                );
            })
            ->values();

        return ScheduleMonthViewModel::collect($months, DataCollection::class);
    }

    private static function buildExams(StudentSchedule $schedule): DataCollection
    {
        $courses = $schedule->items
            ->map(fn ($item) => $item->courseClass->course)
            ->unique('id')
            ->values();

        $exams = $courses
            ->filter(fn ($course) => $course->midterm_date || $course->final_date || $course->exam_time_start || $course->exam_time_end)
            ->map(function ($course) use ($schedule) {
                $firstClass = $schedule->items->first(
                    fn ($item) => $item->courseClass->course->id === $course->id,
                )?->courseClass;

                return ScheduleExamViewModel::fromCourse($course, $firstClass);
            })
            ->sortBy(fn (ScheduleExamViewModel $exam) => $exam->earliestExamAt?->getTimestamp() ?? PHP_INT_MAX)
            ->values();

        return ScheduleExamViewModel::collect($exams, DataCollection::class);
    }
}
