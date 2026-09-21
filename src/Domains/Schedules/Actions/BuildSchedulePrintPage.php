<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use NouTools\Domains\Schedules\PageData\SchedulePrintPageData;
use NouTools\Domains\Schedules\ViewModels\ScheduleCourseItemViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleExamViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintExamRowViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintMonthViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleViewModel;

final readonly class BuildSchedulePrintPage
{
    public function __construct(private ShowSchedulePage $showSchedulePage) {}

    public function __invoke(StudentSchedule $schedule, ?string $term = null): SchedulePrintPageData
    {
        $viewModel = ($this->showSchedulePage)($schedule, $term);

        $courseModels = $schedule->items
            ->map(fn ($item) => $item->course)
            ->unique('id')
            ->values();
        $shareUrl = route('schedules.show', $viewModel->uuid);

        return new SchedulePrintPageData(
            name: $viewModel->name ?: '我的課表',
            semesterLabel: Str::toSemesterDisplay($viewModel->selectedTerm),
            shareUrl: $shareUrl,
            qrCodeSvg: DNS2D::getBarcodeSVG($shareUrl, 'QRCODE'),
            courses: $courseModels
                ->map(fn ($course) => new SchedulePrintCourseViewModel(name: $course->name, credits: $course->credits))
                ->all(),
            exams: $this->examRows($viewModel),
            months: $this->months($viewModel),
        );
    }

    /**
     * One row per course that has an exam date: the midterm and final share a
     * time slot, so the row carries the time once plus the dates split by
     * weekday. Courses with no published date are left out; they are still in
     * the course list. Summer terms have no midterm, matching the schedule
     * page.
     *
     * @return array<int, SchedulePrintExamRowViewModel>
     */
    private function examRows(ScheduleViewModel $viewModel): array
    {
        $hasMidterm = ! str_ends_with($viewModel->selectedTerm, 'C');
        $rows = [];

        /** @var ScheduleExamViewModel $exam */
        foreach ($viewModel->exams as $exam) {
            $buckets = ['saturday' => [], 'sunday' => [], 'other' => []];
            $sittings = [];

            if ($hasMidterm && $exam->midtermDate) {
                $sittings[] = ['期中考', $exam->midtermDate];
            }

            if ($exam->finalDate) {
                $sittings[] = ['期末考', $exam->finalDate];
            }

            foreach ($sittings as [$kind, $date]) {
                match ($date->dayOfWeek) {
                    CarbonInterface::SATURDAY => $buckets['saturday'][] = ['kind' => $kind, 'label' => $date->format('n/j')],
                    CarbonInterface::SUNDAY => $buckets['sunday'][] = ['kind' => $kind, 'label' => $date->format('n/j')],
                    default => $buckets['other'][] = ['kind' => $kind, 'label' => $date->isoFormat('M/D (dd)')],
                };
            }

            if ($sittings === []) {
                continue;
            }

            $rows[] = new SchedulePrintExamRowViewModel(
                courseName: $exam->courseName,
                time: $exam->formattedExamTime,
                saturday: $buckets['saturday'],
                sunday: $buckets['sunday'],
                other: $buckets['other'],
            );
        }

        return $rows;
    }

    /**
     * Every month from the first class to the last, gaps included, so the
     * sheet reads as one continuous semester.
     *
     * @return array<int, SchedulePrintMonthViewModel>
     */
    private function months(ScheduleViewModel $viewModel): array
    {
        $classDays = [];

        foreach ($viewModel->months as $month) {
            foreach ($month->dates as $date) {
                $courses = [];

                /** @var ScheduleCourseItemViewModel $course */
                foreach ($date->courses as $course) {
                    $courses[] = [
                        'name' => $course->courseName,
                        'time' => $course->time === '未設定' ? null : Str::before($course->time, ' - '),
                    ];
                }

                usort($courses, fn (array $a, array $b) => strcmp((string) $a['time'], (string) $b['time']));

                $classDays[$date->dateKey] = ['label' => $date->formattedDate, 'courses' => $courses];
            }
        }

        if ($classDays === []) {
            return [];
        }

        ksort($classDays);

        $keys = array_keys($classDays);
        $cursor = CarbonImmutable::parse($keys[0])->startOfMonth();
        $last = CarbonImmutable::parse(end($keys))->startOfMonth();
        $months = [];

        while ($cursor <= $last) {
            $prefix = $cursor->format('Y-m');

            $months[] = new SchedulePrintMonthViewModel(
                title: $cursor->isoFormat('Y 年 M 月'),
                weeks: $this->weeks($cursor, $classDays),
                classDays: array_values(array_filter(
                    $classDays,
                    fn (string $dateKey) => str_starts_with($dateKey, $prefix),
                    ARRAY_FILTER_USE_KEY,
                )),
            );
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @param  array<string, mixed>  $classDays  Keyed by Y-m-d.
     * @return array<int, array<int, array{day: int, hasClass: bool}|null>>
     */
    private function weeks(CarbonImmutable $firstOfMonth, array $classDays): array
    {
        $cells = array_fill(0, $firstOfMonth->isoWeekday() - 1, null);

        for ($day = 1; $day <= $firstOfMonth->daysInMonth; $day++) {
            $cells[] = [
                'day' => $day,
                'hasClass' => isset($classDays[$firstOfMonth->day($day)->format('Y-m-d')]),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return array_chunk($cells, 7);
    }
}
