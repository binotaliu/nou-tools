<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\PageData;

use NouTools\Domains\Schedules\ViewModels\SchedulePrintCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintExamRowViewModel;
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
        public bool $hasMidterm,
        /** @var array<int, SchedulePrintExamRowViewModel> Earliest exam first; only courses with an exam date. */
        public array $exams,
        /** @var array<int, SchedulePrintMonthViewModel> */
        public array $months,
    ) {}
}
