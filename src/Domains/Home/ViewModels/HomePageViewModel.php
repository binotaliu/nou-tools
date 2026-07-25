<?php

namespace NouTools\Domains\Home\ViewModels;

use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class HomePageViewModel extends Data
{
    public function __construct(
        public string $selectedDate,
        #[DataCollectionOf(HomeCourseViewModel::class)]
        public DataCollection $courses,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}
}
