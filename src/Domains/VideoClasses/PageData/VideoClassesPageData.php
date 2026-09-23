<?php

declare(strict_types=1);

namespace NouTools\Domains\VideoClasses\PageData;

use NouTools\Domains\Home\ViewModels\HomeCourseViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class VideoClassesPageData extends Resource
{
    public function __construct(
        public string $selectedDate,
        public string $today,
        #[DataCollectionOf(HomeCourseViewModel::class)]
        public DataCollection $courses,
    ) {}
}
