<?php

declare(strict_types=1);

namespace NouTools\Domains\LearningProgress\ViewModels;

use Spatie\LaravelData\Data;

final class LearningProgressHomeworkEntryViewModel extends Data
{
    public function __construct(
        public int $courseId,
        public int $number,
        public string $label,
        public ?string $deadline,
        public string $note,
        public bool $completed,
    ) {}
}
