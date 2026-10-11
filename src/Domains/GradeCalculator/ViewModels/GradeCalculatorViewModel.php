<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\ViewModels;

use Spatie\LaravelData\Data;

final class GradeCalculatorViewModel extends Data
{
    /**
     * @param  list<GradeCalculatorCourseViewModel>  $courses
     * @param  array{regular: int, midterm: int, final: int}  $defaultWeights
     */
    public function __construct(
        public string $scheduleUuid,
        public ?string $scheduleName,
        public string $term,
        public bool $isSummer,
        public array $defaultWeights,
        public array $courses,
    ) {}
}
