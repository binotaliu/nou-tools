<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Enums\CourseClassType;
use App\Models\CourseClass;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A course class option for the schedule editor's class/section picker.
 * Snake-cased on output/JSON serialization to stay compatible with the
 * editor's inline Alpine.js (`courseClass.start_time`, etc.).
 */
#[MapName(SnakeCaseMapper::class)]
final class ScheduleEditorCourseClassViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $code,
        public CourseClassType $type,
        public string $typeLabel,
        public ?string $startTime,
        public ?string $endTime,
        public ?string $teacherName,
        public bool $isTentative,
    ) {}

    public static function fromModel(CourseClass $class): self
    {
        return new self(
            id: $class->id,
            code: $class->code,
            type: $class->type,
            typeLabel: $class->type->label(),
            startTime: $class->start_time,
            endTime: $class->end_time,
            teacherName: $class->teacher_name,
            isTentative: $class->is_tentative,
        );
    }
}
