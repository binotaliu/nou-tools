<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\DataTransferObjects;

use Spatie\LaravelData\Data;

final class UpdateGradeCalculatorData extends Data
{
    /**
     * @param  array<int|string, array{regular?: ?numeric, midterm?: ?numeric, final?: ?numeric}>  $scores
     * @param  array<int|string, array{regular: numeric, midterm: numeric, final: numeric}>  $weights
     */
    public function __construct(
        public array $scores = [],
        public array $weights = [],
    ) {}

    public static function rules(): array
    {
        return [
            'scores' => ['present', 'array'],
            'scores.*' => ['array'],
            'scores.*.regular' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.midterm' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.final' => ['nullable', 'numeric', 'between:0,100'],
            'weights' => ['present', 'array'],
            'weights.*' => ['array'],
            'weights.*.regular' => ['required', 'numeric', 'between:0,100'],
            'weights.*.midterm' => ['required', 'numeric', 'between:0,100'],
            'weights.*.final' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
