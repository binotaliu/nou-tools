<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use App\Enums\StudyActivityVerb;
use Illuminate\Validation\Rule;
use NouTools\Domains\StudyRoom\DataTransferObjects\Concerns\ValidatesSubjectCourseId;
use Spatie\LaravelData\Data;

final class ChangeStudyActivityData extends Data
{
    use ValidatesSubjectCourseId;

    public function __construct(
        public StudyActivityVerb $verb,
        public ?int $subjectCourseId,
    ) {}

    public static function rules(): array
    {
        return [
            'verb' => ['required', Rule::enum(StudyActivityVerb::class)],
            'subjectCourseId' => ['nullable', 'integer', self::belongsToViewerScheduleRule()],
        ];
    }

    public static function attributes(): array
    {
        return [
            'verb' => '活動',
            'subjectCourseId' => '科目',
        ];
    }
}
