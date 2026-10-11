<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\ViewModels;

use Spatie\LaravelData\Data;

final class GradeCalculatorCourseViewModel extends Data
{
    /**
     * @param  array{regular: ?float, midterm: ?float, final: ?float}  $scores
     * @param  array{regular: float, midterm: float, final: float}  $weights
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?int $credits,
        public array $scores,
        public array $weights,
    ) {}
}
