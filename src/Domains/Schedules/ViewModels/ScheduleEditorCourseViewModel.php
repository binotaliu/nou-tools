<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\Course;
use NouTools\Domains\Courses\ViewModels\CourseScheduleGroupViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ScheduleEditorCourseViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $term,
        public ?string $department,
        public ?int $credits,
        public ?string $examLabel,
        public ?int $examWeekdayOrder,
        public ?string $examTimeStart,
        public bool $hasClasses,
        #[DataCollectionOf(ScheduleEditorCourseClassViewModel::class)]
        public DataCollection $classes,
    ) {}

    public static function fromModel(Course $course): self
    {
        $hasExam = $course->final_date && $course->exam_time_start;

        return new self(
            id: $course->id,
            name: $course->name,
            term: $course->term,
            department: $course->department,
            credits: $course->credits,
            examLabel: $hasExam ? CourseScheduleGroupViewModel::examLabel($course) : null,
            examWeekdayOrder: $hasExam ? $course->final_date->dayOfWeekIso : null,
            examTimeStart: $hasExam ? $course->exam_time_start : null,
            hasClasses: $course->classes->isNotEmpty(),
            classes: ScheduleEditorCourseClassViewModel::collect(
                $course->classes->map(fn ($class) => ScheduleEditorCourseClassViewModel::fromModel($class)),
                DataCollection::class,
            ),
        );
    }
}
