<?php

namespace NouTools\Domains\LearningProgress\ViewModels;

use Spatie\LaravelData\Data;

final class LearningProgressWeekViewModel extends Data
{
    public function __construct(
        public int $num,
        public string $start,
        public string $end,
    ) {}

    /**
     * @param  array{num: int, start: string, end: string}  $week
     */
    public static function fromArray(array $week): self
    {
        return new self(
            num: $week['num'],
            start: $week['start'],
            end: $week['end'],
        );
    }
}
