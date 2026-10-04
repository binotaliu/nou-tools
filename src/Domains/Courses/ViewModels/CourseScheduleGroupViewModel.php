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

    public static function examLabel(Course $course): string
    {
        return sprintf(
            '%s %s - %s',
            $course->final_date->isoFormat('dddd'),
            $course->exam_time_start,
            $course->exam_time_end,
        );
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    public static function fromCourses(Collection $courses): self
    {
        $first = $courses->first();

        return new self(
            label: self::examLabel($first),
            weekdayOrder: $first->final_date->dayOfWeekIso,
            examTimeStart: $first->exam_time_start,
            courses: CourseScheduleCourseViewModel::collect(
                $courses->sortBy('name')->map(fn (Course $course) => CourseScheduleCourseViewModel::fromModel($course))->values(),
                DataCollection::class,
            ),
        );
    }
}
