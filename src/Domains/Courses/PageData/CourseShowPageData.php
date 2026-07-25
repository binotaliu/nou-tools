<?php

namespace NouTools\Domains\Courses\PageData;

use App\Models\Course;
use NouTools\Domains\Courses\ViewModels\Api\CourseDetailViewModel;
use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;
use Spatie\LaravelData\Resource;

final class CourseShowPageData extends Resource
{
    public function __construct(
        public CourseDetailViewModel $course,
        public ?string $inPersonClassType,
        public ?string $media,
        public ?string $multimediaUrl,
        public ?StudentScheduleCookieViewModel $previousSchedule,
    ) {}

    public static function fromModel(Course $course, CourseDetailViewModel $courseDetail, ?StudentScheduleCookieViewModel $previousSchedule): self
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
