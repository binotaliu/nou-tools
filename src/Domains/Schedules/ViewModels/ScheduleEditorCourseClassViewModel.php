<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Enums\CourseClassType;
use App\Models\CourseClass;
use Illuminate\Support\Str;
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
            typeLabel: $class->is_tentative
                ? (self::labelFromCode($class->code) ?? $class->type->label())
                : $class->type->label(),
            startTime: $class->start_time,
            endTime: $class->end_time,
            teacherName: $class->teacher_name,
            isTentative: $class->is_tentative,
        );
    }

    /**
     * Tentative classes imported from 選課注意事項 are coded
     * "NOTICE-{ORIGINAL_TYPE}" (e.g. "NOTICE-MORNING"), which may differ
     * from the class's stored type when the course's exam category
     * overrides it (see ImportCourseSelectSimulation). The label shown to
     * students should reflect the original session type from the code.
     */
    private static function labelFromCode(string $code): ?string
    {
        return CourseClassType::tryFrom(Str::lower(Str::after($code, 'NOTICE-')))?->label();
    }
}
