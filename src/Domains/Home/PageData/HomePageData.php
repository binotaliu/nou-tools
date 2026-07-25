<?php

namespace NouTools\Domains\Home\PageData;

use NouTools\Domains\Home\ViewModels\HomeCourseViewModel;
use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class HomePageData extends Resource
{
    public function __construct(
        public string $selectedDate,
        #[DataCollectionOf(HomeCourseViewModel::class)]
        public DataCollection $courses,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}
}
