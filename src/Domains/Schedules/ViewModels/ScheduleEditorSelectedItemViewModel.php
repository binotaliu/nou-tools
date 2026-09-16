<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentScheduleItem;
use Spatie\LaravelData\Data;

final class ScheduleEditorSelectedItemViewModel extends Data
{
    public function __construct(
        public int $courseId,
        public ?int $classId,
    ) {}

    public static function fromModel(StudentScheduleItem $item): self
    {
        return new self(
            courseId: $item->course_id,
            classId: $item->course_class_id,
        );
    }
}
