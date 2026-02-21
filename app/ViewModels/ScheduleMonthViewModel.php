<?php

namespace App\ViewModels;

use Illuminate\Support\Collection;

final readonly class ScheduleMonthViewModel
{
    /**
     * @param  Collection<int, \App\ViewModels\ScheduleDateViewModel>  $dates
     */
    public function __construct(
        public string $monthKey,
        public string $monthDisplay,
        public Collection $dates,
    ) {}
}
