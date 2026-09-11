<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use App\Models\StudentScheduleItem;
use Closure;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class StartStudyTimerData extends Data
{
    public function __construct(
        public StudyTimerMode $mode,
        public ?int $minutes,
        public StudyActivityVerb $verb,
        public ?int $subjectCourseId,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $minMinutes = (int) config('study-room.timer.custom.min_minutes');
        $maxMinutes = (int) config('study-room.timer.custom.max_minutes');
        $mode = $context->payload['mode'] ?? null;

        return [
            'mode' => ['required', Rule::enum(StudyTimerMode::class)],
            'minutes' => [
                Rule::requiredIf($mode === StudyTimerMode::Custom->value),
                'nullable',
                'integer',
                "min:{$minMinutes}",
                "max:{$maxMinutes}",
            ],
            'verb' => ['required', Rule::enum(StudyActivityVerb::class)],
            'subjectCourseId' => ['nullable', 'integer', self::belongsToViewerScheduleRule()],
        ];
    }

    public static function attributes(): array
    {
        return [
            'mode' => '計時模式',
            'minutes' => '分鐘數',
            'verb' => '活動',
            'subjectCourseId' => '科目',
        ];
    }

    /**
     * A non-null subject must be a course in the caller's own schedule for
     * the current term — otherwise anyone could claim to be studying any
     * course id, including one they aren't even enrolled in.
     */
    private static function belongsToViewerScheduleRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null) {
                return;
            }

            $viewer = request()->studentScheduleFromCookie();
            $term = (string) config('app.current_semester');

            $belongsToViewer = $viewer !== null && StudentScheduleItem::query()
                ->where('student_schedule_id', $viewer->id)
                ->where('course_id', $value)
                ->whereHas('course', fn ($query) => $query->where('term', $term))
                ->exists();

            if (! $belongsToViewer) {
                $fail('選擇的科目不屬於你目前的課表。');
            }
        };
    }
}
