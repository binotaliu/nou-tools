<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\PageData;

use App\Models\Course;
use NouTools\Domains\Courses\ViewModels\Api\CourseDetailViewModel;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use Spatie\LaravelData\Resource;

final class CourseShowPageData extends Resource
{
    public function __construct(
        public CourseDetailViewModel $course,
        public ?string $inPersonClassType,
        public ?string $media,
        public ?string $multimediaUrl,
        public ?StudentScheduleCookie $previousSchedule,
    ) {}

    public static function fromModel(Course $course, CourseDetailViewModel $courseDetail, ?StudentScheduleCookie $previousSchedule): self
    {
        return new self(
            course: $courseDetail,
            inPersonClassType: $course->in_person_class_type,
            media: $course->media,
            multimediaUrl: $course->multimedia_url,
            previousSchedule: $previousSchedule,
        );
    }
}
