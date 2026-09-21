<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use NouTools\Domains\Schedules\PageData\SchedulePrintPageData;
use NouTools\Domains\Schedules\ViewModels\ScheduleExamViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintExamDayViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintMonthViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleViewModel;

final readonly class BuildSchedulePrintPage
{
    public function __construct(private ShowSchedulePage $showSchedulePage) {}

    public function __invoke(StudentSchedule $schedule, ?string $term = null): SchedulePrintPageData
    {
        $viewModel = ($this->showSchedulePage)($schedule, $term);

        $courses = $this->courses($schedule);
        $days = $this->examDays($viewModel);
        $datedCourseNames = collect($days)->flatMap(fn (SchedulePrintExamDayViewModel $day) => array_column($day->entries, 'courseName'))->all();
        $shareUrl = route('schedules.show', $viewModel->uuid);

        return new SchedulePrintPageData(
            name: $viewModel->name ?: '我的課表',
            semesterLabel: Str::toSemesterDisplay($viewModel->selectedTerm),
            shareUrl: $shareUrl,
            qrCodeSvg: DNS2D::getBarcodeSVG($shareUrl, 'QRCODE'),
            courses: $courses,
            saturdayExams: $this->daysOn($days, CarbonInterface::SATURDAY),
            sundayExams: $this->daysOn($days, CarbonInterface::SUNDAY),
            otherExams: array_values(array_filter(
                $days,
                fn (SchedulePrintExamDayViewModel $day) => ! in_array(CarbonImmutable::parse($day->dateKey)->dayOfWeek, [CarbonInterface::SATURDAY, CarbonInterface::SUNDAY], true),
            )),
            undatedCourseNames: array_values(array_diff(
                array_map(fn (SchedulePrintCourseViewModel $course) => $course->name, $courses),
                $datedCourseNames,
            )),
            months: $this->months($viewModel),
        );
    }

    /**
     * @return array<int, SchedulePrintCourseViewModel>
     */
    private function courses(StudentSchedule $schedule): array
    {
        return $schedule->items
            ->map(fn ($item) => $item->course)
            ->unique('id')
            ->map(fn ($course) => new SchedulePrintCourseViewModel(name: $course->name, credits: $course->credits))
            ->values()
            ->all();
    }

    /**
     * One entry per date, listing each course sitting on it, so a date reads as
     * a single heading however many exams share the day. Summer terms have no
     * midterm, matching the schedule page.
     *
     * @return array<int, SchedulePrintExamDayViewModel>
     */
    private function examDays(ScheduleViewModel $viewModel): array
    {
        $hasMidterm = ! str_ends_with($viewModel->selectedTerm, 'C');
        $days = [];

        /** @var ScheduleExamViewModel $exam */
        foreach ($viewModel->exams as $exam) {
            $sittings = [];

            if ($hasMidterm && $exam->midtermDate) {
                $sittings[] = [$exam->midtermDate, $exam->formattedMidtermDate, '期中考'];
            }

            if ($exam->finalDate) {
                $sittings[] = [$exam->finalDate, $exam->formattedFinalDate, '期末考'];
            }

            foreach ($sittings as [$date, $label, $kind]) {
                $dateKey = $date->format('Y-m-d');
                $days[$dateKey] ??= new SchedulePrintExamDayViewModel(dateKey: $dateKey, dateLabel: (string) $label, entries: []);
                $days[$dateKey]->entries[] = [
                    'kind' => $kind,
                    'time' => $exam->formattedExamTime,
                    'courseName' => $exam->courseName,
                ];
            }
        }

        ksort($days);

        foreach ($days as $day) {
            usort($day->entries, fn (array $a, array $b) => strcmp((string) $a['time'], (string) $b['time']));
        }

        return array_values($days);
    }

    /**
     * @param  array<int, SchedulePrintExamDayViewModel>  $days
     * @return array<int, SchedulePrintExamDayViewModel>
     */
    private function daysOn(array $days, int $dayOfWeek): array
    {
        return array_values(array_filter(
            $days,
            fn (SchedulePrintExamDayViewModel $day) => CarbonImmutable::parse($day->dateKey)->dayOfWeek === $dayOfWeek,
        ));
    }

    /**
     * Every month from the first class to the last, gaps included, so the
     * sheet reads as one continuous semester.
     *
     * @return array<int, SchedulePrintMonthViewModel>
     */
    private function months(ScheduleViewModel $viewModel): array
    {
        $classDates = [];

        foreach ($viewModel->months as $month) {
            foreach ($month->dates as $date) {
                $classDates[$date->dateKey] = true;
            }
        }

        if ($classDates === []) {
            return [];
        }

        $keys = array_keys($classDates);
        sort($keys);

        $cursor = CarbonImmutable::parse($keys[0])->startOfMonth();
        $last = CarbonImmutable::parse(end($keys))->startOfMonth();
        $months = [];

        while ($cursor <= $last) {
            $months[] = new SchedulePrintMonthViewModel(
                title: $cursor->isoFormat('Y 年 M 月'),
                weeks: $this->weeks($cursor, $classDates),
            );
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @param  array<string, true>  $classDates
     * @return array<int, array<int, array{day: int, hasClass: bool}|null>>
     */
    private function weeks(CarbonImmutable $firstOfMonth, array $classDates): array
    {
        $cells = array_fill(0, $firstOfMonth->isoWeekday() - 1, null);

        for ($day = 1; $day <= $firstOfMonth->daysInMonth; $day++) {
            $cells[] = [
                'day' => $day,
                'hasClass' => isset($classDates[$firstOfMonth->day($day)->format('Y-m-d')]),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return array_chunk($cells, 7);
    }
}
