<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Enums\CourseClassType;
use App\Models\CourseClass;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CourseClassViewModel extends Data
{
    public function __construct(
        public int $id,
        public int $courseId,
        public string $courseName,
        public string $code,
        public CourseClassType $type,
        public ?string $startTime,
        public ?string $endTime,
        public ?string $teacherName,
        public ?string $link,
        public ?string $backupClassroomUrl,
        #[DataCollectionOf(CourseClassScheduleViewModel::class)]
        public DataCollection $schedules,
    ) {}

    public static function fromModel(CourseClass $class): self
    {
        return new self(
            id: $class->id,
            courseId: $class->course_id,
            courseName: $class->course->name,
            code: $class->code,
            type: $class->type,
            startTime: $class->start_time,
            endTime: $class->end_time,
            teacherName: $class->teacher_name,
            link: $class->link,
            backupClassroomUrl: $class->backup_classroom_url,
            schedules: CourseClassScheduleViewModel::collect(
                $class->schedules->map(fn ($s) => CourseClassScheduleViewModel::fromModel($s)),
                DataCollection::class,
            ),
        );
    }
}
