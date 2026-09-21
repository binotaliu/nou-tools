<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

final class SchedulePrintMonthViewModel extends Data
{
    public function __construct(
        public string $title,
        /** @var array<int, array<int, array{day: int, hasClass: bool, isExam: bool}|null>> Weeks (Monday- or Sunday-first) of seven cells; null pads days outside the month. */
        public array $weeks,
        /** @var array<int, array{label: string, courses: array<int, array{name: string, time: ?string}>}> Class dates in order, with what is held on each. */
        public array $classDays,
        /** @var array<int, array{label: string, kind: string, courses: array<int, string>}> Exam dates in order, one entry per date and exam kind. */
        public array $examDays,
    ) {}
}
