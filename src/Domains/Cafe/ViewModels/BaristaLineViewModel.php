<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use Spatie\LaravelData\Data;

final class BaristaLineViewModel extends Data
{
    /**
     * @param  array<int, array{label: string, response: string, action: string}>  $replies
     */
    public function __construct(
        public int $id,
        public string $trigger,
        public string $text,
        public string $mood,
        public array $replies,
    ) {}
}
