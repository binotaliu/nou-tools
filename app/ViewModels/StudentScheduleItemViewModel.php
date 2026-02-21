<?php

namespace App\ViewModels;

use App\Models\StudentScheduleItem;
use Spatie\LaravelData\Data;

final class StudentScheduleItemViewModel extends Data
{
    public function __construct(
        public int $id,
        public int $courseClassId,
        public object $courseClass,
    ) {}

    public static function fromModel(StudentScheduleItem $item): self
    {
        return new self(
            id: $item->id,
            courseClassId: $item->course_class_id,
            courseClass: $item->courseClass,
        );
    }
}
