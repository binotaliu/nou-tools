<?php

namespace NouTools\Domains\Courses\ViewModels;

use App\Models\Course;
use Illuminate\Support\Collection;
use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;

final readonly class CourseShowPageViewModel
{
    public function __construct(
        public Course $course,
        public ?StudentScheduleCookieViewModel $previousSchedule,
        public Collection $previousExams,
    ) {}
}
