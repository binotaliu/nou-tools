<?php

namespace NouTools\Domains\LearningProgress\DataTransferObjects;

use Spatie\LaravelData\Data;

final class UpdateLearningProgressData extends Data
{
    public function __construct(
        public array $progress = [],
        public array $notes = [],
    ) {}

    public static function rules(): array
    {
        return [
            'progress' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'array'],
        ];
    }
}
