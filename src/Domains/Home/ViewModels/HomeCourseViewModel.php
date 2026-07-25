<?php

namespace NouTools\Domains\Home\ViewModels;

use App\Models\Course;
use NouTools\Domains\Courses\ViewModels\Api\ClassWithSessionsViewModel;
use NouTools\Domains\Courses\ViewModels\Api\CourseSummaryViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * A course with its in-person class sections for a given day, as shown on the home page (今日視訊面授).
 */
final class HomeCourseViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $term,
        #[DataCollectionOf(ClassWithSessionsViewModel::class)]
        public DataCollection $classes,
    ) {}

    public static function fromModel(Course $course): self
    {
        $summary = CourseSummaryViewModel::fromModel($course);

        return new self(
            id: $summary->id,
            name: $summary->name,
            term: $summary->term,
            classes: ClassWithSessionsViewModel::collect(
                $course->classes->map(fn ($class) => ClassWithSessionsViewModel::fromModel($class)),
                DataCollection::class,
            ),
        );
    }
}
