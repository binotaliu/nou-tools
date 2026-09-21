<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\PageData;

use NouTools\Domains\Schedules\ViewModels\SchedulePrintCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintExamDayViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintMonthViewModel;
use Spatie\LaravelData\Resource;

final class SchedulePrintPageData extends Resource
{
    public function __construct(
        public string $name,
        public string $semesterLabel,
        public string $shareUrl,
        public string $qrCodeSvg,
        /** @var array<int, SchedulePrintCourseViewModel> */
        public array $courses,
        /** @var array<int, SchedulePrintExamDayViewModel> */
        public array $saturdayExams,
        /** @var array<int, SchedulePrintExamDayViewModel> */
        public array $sundayExams,
        /** @var array<int, SchedulePrintExamDayViewModel> Exams that fall on a weekday, so bad data never silently disappears. */
        public array $otherExams,
        /** @var array<int, string> Courses with no exam date published yet. */
        public array $undatedCourseNames,
        /** @var array<int, SchedulePrintMonthViewModel> */
        public array $months,
    ) {}
}
