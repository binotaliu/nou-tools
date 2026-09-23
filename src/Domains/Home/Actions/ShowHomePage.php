<?php

declare(strict_types=1);

namespace NouTools\Domains\Home\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Home\DataTransferObjects\ShowHomePageData;
use NouTools\Domains\Home\PageData\HomePageData;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

final readonly class ShowHomePage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
        private ListVideoCourses $listVideoCourses,
    ) {}

    public function __invoke(ShowHomePageData $input, Request $request): HomePageData
    {
        $selectedDate = $this->listVideoCourses->resolveSelectedDate($input->date);

        return new HomePageData(
            selectedDate: $selectedDate,
            today: Date::now('Asia/Taipei')->format('Y-m-d'),
            courses: ($this->listVideoCourses)($selectedDate),
            previousSchedule: ($this->readStudentScheduleCookie)($request),
        );
    }
}
