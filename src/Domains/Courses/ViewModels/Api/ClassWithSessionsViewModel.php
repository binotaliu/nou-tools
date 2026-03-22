<?php

namespace NouTools\Domains\Courses\ViewModels\Api;

use App\Enums\CourseClassType;
use App\Models\CourseClass;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * A class section with all its in-person session dates and video link (視訊面授班級).
 */
final class ClassWithSessionsViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $code,
        public CourseClassType $type,
        public string $typeLabel,
        public string $startTime,
        public string $endTime,
        public string $teacherName,
        public string $link,
        #[DataCollectionOf(ClassSessionViewModel::class)]
        public DataCollection $sessions,
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
            link: $class->link,
            sessions: ClassSessionViewModel::collect(
                $class->schedules->map(fn ($s) => ClassSessionViewModel::fromModel($s)),
                DataCollection::class,
            ),
        );
    }
}
