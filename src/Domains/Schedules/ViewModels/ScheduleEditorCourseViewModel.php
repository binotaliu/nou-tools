<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\Course;
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
        public bool $hasClasses,
        #[DataCollectionOf(ScheduleEditorCourseClassViewModel::class)]
        public DataCollection $classes,
    ) {}

    public static function fromModel(Course $course): self
    {
        return new self(
            id: $course->id,
            name: $course->name,
            term: $course->term,
            hasClasses: $course->classes->isNotEmpty(),
            classes: ScheduleEditorCourseClassViewModel::collect(
                $course->classes->map(fn ($class) => ScheduleEditorCourseClassViewModel::fromModel($class)),
                DataCollection::class,
            ),
        );
    }
}
