<?php

declare(strict_types=1);

namespace NouTools\Domains\VideoClasses\Actions;

use Illuminate\Support\Facades\Date;
use NouTools\Domains\Home\Actions\ListVideoCourses;
use NouTools\Domains\VideoClasses\DataTransferObjects\ShowVideoClassesData;
use NouTools\Domains\VideoClasses\PageData\VideoClassesPageData;

final readonly class ShowVideoClassesPage
{
    public function __construct(
        private ListVideoCourses $listVideoCourses,
    ) {}

    public function __invoke(ShowVideoClassesData $input): VideoClassesPageData
    {
        $selectedDate = $this->listVideoCourses->resolveSelectedDate($input->date);

        return new VideoClassesPageData(
            selectedDate: $selectedDate,
            today: Date::now('Asia/Taipei')->format('Y-m-d'),
            courses: ($this->listVideoCourses)($selectedDate),
        );
    }
}
