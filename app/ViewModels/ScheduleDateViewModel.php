<?php

namespace App\ViewModels;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ScheduleDateViewModel extends Data
{
    public function __construct(
        public CarbonInterface $date,
        public string $dateKey,
        #[DataCollectionOf(ScheduleCourseItemViewModel::class)]
        public DataCollection $courses,
    ) {}

    public function formattedDate(): string
    {
        return $this->date->isoFormat('M/D (dd)');
    }
}
