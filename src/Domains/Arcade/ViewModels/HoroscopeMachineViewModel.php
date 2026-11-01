<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\ViewModels;

use Spatie\LaravelData\Data;

final class HoroscopeMachineViewModel extends Data
{
    /**
     * @param  array<int, array{value: string, label: string, symbol: string, dateRange: string}>  $signs
     * @param  array<int, HoroscopeReadingViewModel>  $unlocked  today's readings the viewer has already paid for
     */
    public function __construct(
        public int $price,
        public string $today,
        public array $signs,
        public array $unlocked,
    ) {}
}
