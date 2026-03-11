<?php

namespace NouTools\Domains\Home\ViewModels;

use Illuminate\Support\Collection;
use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;

final readonly class HomePageViewModel
{
    public function __construct(
        public string $selectedDate,
        public Collection $courses,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}
}
