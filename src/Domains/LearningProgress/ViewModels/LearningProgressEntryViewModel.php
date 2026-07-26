<?php

declare(strict_types=1);

namespace NouTools\Domains\LearningProgress\ViewModels;

use Spatie\LaravelData\Data;

final class LearningProgressEntryViewModel extends Data
{
    public function __construct(
        public int $courseId,
        public int $weekNum,
        public bool $videoCompleted,
        public bool $textbookCompleted,
        public string $note,
    ) {}
}
