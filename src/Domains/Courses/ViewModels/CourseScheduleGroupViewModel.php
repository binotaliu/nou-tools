<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\ViewModels;

use App\Models\Course;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CourseScheduleGroupViewModel extends Data
{
    public function __construct(
        public string $label,
        public int $weekdayOrder,
        public ?string $examTimeStart,
        #[DataCollectionOf(CourseScheduleCourseViewModel::class)]
        public DataCollection $courses,
    ) {}

    /**
     * @param  Collection<int, Course>  $courses
     */
    public static function fromCourses(Collection $courses): self
    {
        $first = $courses->first();

        return new self(
            label: sprintf(
                '%s %s - %s',
                $first->final_date->isoFormat('dddd'),
                $first->exam_time_start,
                $first->exam_time_end,
            ),
            weekdayOrder: $first->final_date->dayOfWeekIso,
            examTimeStart: $first->exam_time_start,
            courses: CourseScheduleCourseViewModel::collect(
                $courses->sortBy('name')->map(fn (Course $course) => CourseScheduleCourseViewModel::fromModel($course))->values(),
                DataCollection::class,
            ),
        );
    }
}
