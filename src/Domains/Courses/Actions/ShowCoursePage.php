<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use App\Models\Course;
use Illuminate\Http\Request;
use NouTools\Domains\Courses\PageData\CourseShowPageData;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

final readonly class ShowCoursePage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
        private GetCourseDetail $getCourseDetail,
    ) {}

    public function __invoke(Course $course, Request $request): CourseShowPageData
    {
        $previousSchedule = ($this->readStudentScheduleCookie)($request);
        $courseDetail = ($this->getCourseDetail)($course);

        return CourseShowPageData::fromModel($course, $courseDetail, $previousSchedule);
    }
}
