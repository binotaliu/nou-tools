<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentScheduleItem;
use Spatie\LaravelData\Data;

final class StudentScheduleItemViewModel extends Data
{
    public function __construct(
        public int $id,
        public int $courseClassId,
        public CourseClassViewModel $courseClass,
    ) {}

    public static function fromModel(StudentScheduleItem $item): self
    {
        return new self(
            id: $item->id,
            courseClassId: $item->course_class_id,
            courseClass: CourseClassViewModel::fromModel($item->courseClass),
        );
    }
}
