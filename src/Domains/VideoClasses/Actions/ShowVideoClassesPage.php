<?php

declare(strict_types=1);

namespace NouTools\Domains\VideoClasses\Actions;

use Illuminate\Support\Facades\Date;
use NouTools\Domains\Home\Actions\ListVideoCourses;
use NouTools\Domains\Home\Actions\ResolveVideoCourseDate;
use NouTools\Domains\VideoClasses\DataTransferObjects\ShowVideoClassesData;
use NouTools\Domains\VideoClasses\PageData\VideoClassesPageData;

final readonly class ShowVideoClassesPage
{
    public function __construct(
        private ListVideoCourses $listVideoCourses,
        private ResolveVideoCourseDate $resolveVideoCourseDate,
    ) {}

    public function __invoke(ShowVideoClassesData $input): VideoClassesPageData
    {
        $selectedDate = ($this->resolveVideoCourseDate)($input->date);

        return new VideoClassesPageData(
            selectedDate: $selectedDate,
            today: Date::now('Asia/Taipei')->format('Y-m-d'),
            courses: ($this->listVideoCourses)($selectedDate),
        );
    }
}
