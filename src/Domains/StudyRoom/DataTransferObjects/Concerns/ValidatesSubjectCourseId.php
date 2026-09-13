<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects\Concerns;

use App\Models\StudentScheduleItem;
use Closure;

trait ValidatesSubjectCourseId
{
    /**
     * A non-null subject must be a course in the caller's own schedule for
     * the current term — otherwise anyone could claim to be studying any
     * course id, including one they aren't even enrolled in.
     */
    protected static function belongsToViewerScheduleRule(): Closure
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
