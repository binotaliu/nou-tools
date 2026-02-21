<?php

namespace App\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ScheduleMonthViewModel extends Data
{
    public function __construct(
        public string $monthKey,
        public string $monthDisplay,
        #[DataCollectionOf(ScheduleDateViewModel::class)]
        public DataCollection $dates,
    ) {}
}
