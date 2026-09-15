<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\Course;
use App\Models\CourseClass;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Data;

final class ScheduleExamViewModel extends Data
{
    public function __construct(
        public int $courseId,
        public string $courseName,
        public ?string $classCode,
        public ?CarbonInterface $midtermDate,
        public ?CarbonInterface $finalDate,
        public ?string $examTimeStart,
        public ?string $examTimeEnd,
        public ?CarbonInterface $earliestExamAt,
        public bool $isTentative,
        public ?string $formattedMidtermDate,
        public ?string $formattedFinalDate,
        public ?string $formattedExamTime,
    ) {}

    public static function fromCourse(Course $course, ?CourseClass $firstClass): self
    {
        $dates = collect();

        if ($course->midterm_date) {
            $midtermDate = Date::parse($course->midterm_date);

            if ($course->exam_time_start) {
                $midtermDate = $midtermDate->setTimeFromTimeString($course->exam_time_start);
            }

            $dates->push($midtermDate);
        }

        if ($course->final_date) {
            $finalDate = Date::parse($course->final_date);

            if ($course->exam_time_start) {
                $finalDate = $finalDate->setTimeFromTimeString($course->exam_time_start);
            }

            $dates->push($finalDate);
        }

        $midtermDate = $course->midterm_date ? Date::parse($course->midterm_date) : null;
        $finalDate = $course->final_date ? Date::parse($course->final_date) : null;

        $formattedExamTime = match (true) {
            ! $course->exam_time_start && ! $course->exam_time_end => null,
            (bool) $course->exam_time_start && (bool) $course->exam_time_end => "{$course->exam_time_start} - {$course->exam_time_end}",
            default => $course->exam_time_start ?? $course->exam_time_end,
        };

        return new self(
            courseId: $course->id,
            courseName: $course->name,
            classCode: $firstClass?->code,
            midtermDate: $midtermDate,
            finalDate: $finalDate,
            examTimeStart: $course->exam_time_start,
            examTimeEnd: $course->exam_time_end,
            earliestExamAt: $dates->count() > 0 ? $dates->min() : null,
            isTentative: $firstClass === null || $firstClass->is_tentative,
            formattedMidtermDate: $midtermDate?->isoFormat('M/D (dd)'),
            formattedFinalDate: $finalDate?->isoFormat('M/D (dd)'),
            formattedExamTime: $formattedExamTime,
        );
    }
}
