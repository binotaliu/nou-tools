<?php

namespace App\ViewModels;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class ScheduleDateViewModel
{
    /**
     * @param  Collection<int, \App\ViewModels\ScheduleCourseItemViewModel>  $courses
     */
    public function __construct(
        public CarbonInterface $date,
        public string $dateKey,
        public Collection $courses,
    ) {}

    public function formattedDate(): string
    {
        return $this->date->isoFormat('M/D (dd)');
    }
}
