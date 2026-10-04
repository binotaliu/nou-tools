<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\CourseClass;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A 專班 for the schedule editor's "加入專班課程" picker. Each course carries
 * exactly this 專班's class, so adding it needs no class choice.
 */
#[MapName(SnakeCaseMapper::class)]
final class ScheduleEditorProgramViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $region,
        public string $regionLabel,
        #[DataCollectionOf(ScheduleEditorCourseViewModel::class)]
        public DataCollection $courses,
    ) {}

    public static function fromModel(Program $program): self
    {
        $courses = $program->classes
            ->map(function (CourseClass $class): ScheduleEditorCourseViewModel {
                $course = $class->course->withoutRelations();
                $course->setRelation('classes', new Collection([$class]));

                return ScheduleEditorCourseViewModel::fromModel($course);
            })
            ->sortBy('name')
            ->values();

        return new self(
            id: $program->id,
            name: $program->name,
            region: $program->region,
            regionLabel: (string) config("special_programs.regions.{$program->region}.label", $program->region),
            courses: ScheduleEditorCourseViewModel::collect($courses, DataCollection::class),
        );
    }
}
