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
        $sittings = $this->sittings($viewModel);

        return new SchedulePrintPageData(
            name: $viewModel->name ?: '我的課表',
            semesterLabel: Str::toSemesterDisplay($viewModel->selectedTerm),
            shareUrl: $shareUrl,
            qrCodeSvg: DNS2D::getBarcodeSVG($shareUrl, 'QRCODE'),
            courses: $courseModels
                ->map(fn ($course) => new SchedulePrintCourseViewModel(name: $course->name, credits: $course->credits))
                ->all(),
            hasMidterm: ! str_ends_with($viewModel->selectedTerm, 'C'),
            exams: $this->examRows($sittings),
            months: $this->months($viewModel, $sittings),
        );
    }

    /**
     * Every exam sitting, in exam order. Midterm and final share a time slot,
     * so the time belongs to the course, not the sitting. Summer terms have no
     * midterm, matching the schedule page.
     *
     * @return array<int, array{courseId: int, courseName: string, time: ?string, kind: string, date: CarbonInterface}>
     */
    private function sittings(ScheduleViewModel $viewModel): array
    {
        $hasMidterm = ! str_ends_with($viewModel->selectedTerm, 'C');
        $sittings = [];

        /** @var ScheduleExamViewModel $exam */
        foreach ($viewModel->exams as $exam) {
            $dates = array_filter([
                '期中考' => $hasMidterm ? $exam->midtermDate : null,
                '期末考' => $exam->finalDate,
            ]);

            foreach ($dates as $kind => $date) {
                $sittings[] = [
                    'courseId' => $exam->courseId,
                    'courseName' => $exam->courseName,
                    'time' => $exam->formattedExamTime,
                    'kind' => $kind,
                    'date' => $date,
                ];
            }
        }

        return $sittings;
    }

    /**
     * One row per course that has an exam date, with the dates split by
     * weekday. Courses with no published date are left out; they are still in
     * the course list.
     *
     * @param  array<int, array{courseId: int, courseName: string, time: ?string, kind: string, date: CarbonInterface}>  $sittings
     * @return array<int, SchedulePrintExamRowViewModel>
     */
    private function examRows(array $sittings): array
    {
        $buckets = [];

        foreach ($sittings as $sitting) {
            $buckets[$sitting['courseId']]['course'] = $sitting;
            $bucket = match ($sitting['date']->dayOfWeek) {
                CarbonInterface::SATURDAY => 'saturday',
                CarbonInterface::SUNDAY => 'sunday',
                default => 'other',
            };
            $buckets[$sitting['courseId']][$bucket][] = [
                'kind' => $sitting['kind'],
                'label' => $bucket === 'other' ? $sitting['date']->isoFormat('M/D (dd)') : $sitting['date']->format('n/j'),
            ];
        }

        return array_values(array_map(fn (array $bucket) => new SchedulePrintExamRowViewModel(
            courseName: $bucket['course']['courseName'],
            time: $bucket['course']['time'],
            saturday: $bucket['saturday'] ?? [],
            sunday: $bucket['sunday'] ?? [],
            other: $bucket['other'] ?? [],
        ), $buckets));
    }

    /**
     * Every month from the first class or exam to the last, gaps included, so
     * the sheet reads as one continuous semester. The final exam usually falls
     * after the last class, so exam dates extend the range.
     *
     * @param  array<int, array{courseId: int, courseName: string, time: ?string, kind: string, date: CarbonInterface}>  $sittings
     * @return array<int, SchedulePrintMonthViewModel>
     */
    private function months(ScheduleViewModel $viewModel, array $sittings): array
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

        $examDays = [];

        foreach ($sittings as $sitting) {
            $dateKey = $sitting['date']->format('Y-m-d');
            $examDays[$dateKey.'|'.$sitting['kind']] ??= [
                'dateKey' => $dateKey,
                'label' => $sitting['date']->isoFormat('M/D (dd)'),
                'kind' => $sitting['kind'],
                'courses' => [],
            ];
            $examDays[$dateKey.'|'.$sitting['kind']]['courses'][] = $sitting['courseName'];
        }

        ksort($classDays);
        ksort($examDays);

        $keys = [...array_keys($classDays), ...array_column($examDays, 'dateKey')];

        if ($keys === []) {
            return [];
        }

        sort($keys);

        $examDateKeys = array_flip(array_column($examDays, 'dateKey'));
        $cursor = CarbonImmutable::parse($keys[0])->startOfMonth();
        $last = CarbonImmutable::parse(end($keys))->startOfMonth();
        $months = [];

        while ($cursor <= $last) {
            $prefix = $cursor->format('Y-m');

            $months[] = new SchedulePrintMonthViewModel(
                title: $cursor->isoFormat('Y 年 M 月'),
                weeks: $this->weeks($cursor, $classDays, $examDateKeys),
                classDays: array_values(array_filter(
                    $classDays,
                    fn (string $dateKey) => str_starts_with($dateKey, $prefix),
                    ARRAY_FILTER_USE_KEY,
                )),
                examDays: array_values(array_map(
                    fn (array $day) => ['label' => $day['label'], 'kind' => $day['kind'], 'courses' => $day['courses']],
                    array_filter($examDays, fn (array $day) => str_starts_with($day['dateKey'], $prefix)),
                )),
            );
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @param  array<string, mixed>  $classDays  Keyed by Y-m-d.
     * @param  array<string, int>  $examDateKeys  Keyed by Y-m-d.
     * @return array<int, array<int, array{day: int, hasClass: bool, isExam: bool}|null>>
     */
    private function weeks(CarbonImmutable $firstOfMonth, array $classDays, array $examDateKeys): array
    {
        $cells = array_fill(0, $firstOfMonth->isoWeekday() - 1, null);

        for ($day = 1; $day <= $firstOfMonth->daysInMonth; $day++) {
            $dateKey = $firstOfMonth->day($day)->format('Y-m-d');
            $cells[] = [
                'day' => $day,
                'hasClass' => isset($classDays[$dateKey]),
                'isExam' => isset($examDateKeys[$dateKey]),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return array_chunk($cells, 7);
    }
}
