<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\Course;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ScheduleEditorCourseViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $term,
        #[DataCollectionOf(ScheduleEditorCourseClassViewModel::class)]
        public DataCollection $classes,
    ) {}

    public static function fromModel(Course $course): self
    {
        return new self(
            id: $course->id,
            name: $course->name,
            term: $course->term,
            classes: ScheduleEditorCourseClassViewModel::collect(
                $course->classes->map(fn ($class) => ScheduleEditorCourseClassViewModel::fromModel($class)),
                DataCollection::class,
            ),
        );
    }
}
