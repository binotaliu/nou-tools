<?php

namespace NouTools\Domains\Courses\ViewModels\Api;

use App\Models\Course;
use Spatie\LaravelData\Data;

/**
 * Minimal course summary for list responses (課程列表).
 */
final class CourseSummaryViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $term,
    ) {}

    public static function fromModel(Course $course): self
    {
        return new self(
            id: $course->id,
            name: $course->name,
            term: $course->term,
        );
    }
}
