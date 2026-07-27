<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\ViewModels;

use App\Models\Course;
use Spatie\LaravelData\Data;

final class CourseScheduleCourseViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $department,
        public ?int $credits,
        public ?string $creditType,
    ) {}

    public static function fromModel(Course $course): self
    {
        return new self(
            id: $course->id,
            name: $course->name,
            department: $course->department,
            credits: $course->credits,
            creditType: $course->credit_type,
        );
    }
}
