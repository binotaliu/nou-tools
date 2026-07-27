<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\PageData;

use NouTools\Domains\Courses\ViewModels\CourseScheduleCourseViewModel;
use NouTools\Domains\Courses\ViewModels\CourseScheduleGroupViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class CourseSchedulePageData extends Resource
{
    public function __construct(
        public string $currentSemester,
        public string $selectedTerm,
        /** @var array<int, string> */
        public array $availableTerms,
        #[DataCollectionOf(CourseScheduleGroupViewModel::class)]
        public DataCollection $groups,
        #[DataCollectionOf(CourseScheduleCourseViewModel::class)]
        public DataCollection $microCreditOrRemoteCourses,
    ) {}
}
