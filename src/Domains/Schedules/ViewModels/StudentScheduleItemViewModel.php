<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentScheduleItem;
use Spatie\LaravelData\Data;

final class StudentScheduleItemViewModel extends Data
{
    public function __construct(
        public int $id,
        public ?int $courseClassId,
        public ?CourseClassViewModel $courseClass,
        public int $courseId,
        public string $courseName,
    ) {}

    public static function fromModel(StudentScheduleItem $item): self
    {
        return new self(
            id: $item->id,
            courseClassId: $item->course_class_id,
            courseClass: $item->courseClass ? CourseClassViewModel::fromModel($item->courseClass) : null,
            courseId: $item->course->id,
            courseName: $item->course->name,
        );
    }
}
