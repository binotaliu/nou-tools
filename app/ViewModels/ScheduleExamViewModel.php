<?php

namespace App\ViewModels;

use App\Models\Course;
use App\Models\CourseClass;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final readonly class ScheduleExamViewModel
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
    ) {}

    public static function fromCourse(Course $course, ?CourseClass $firstClass): self
    {
        $dates = collect();

        if ($course->midterm_date) {
            $dt = Date::parse($course->midterm_date);
            if ($course->exam_time_start) {
                $dt = $dt->setTimeFromTimeString($course->exam_time_start);
            }
            $dates->push($dt);
        }

        if ($course->final_date) {
            $dt = Date::parse($course->final_date);
            if ($course->exam_time_start) {
                $dt = $dt->setTimeFromTimeString($course->exam_time_start);
            }
            $dates->push($dt);
        }

        return new self(
            courseId: $course->id,
            courseName: $course->name,
            classCode: $firstClass?->code,
            midtermDate: $course->midterm_date ? Date::parse($course->midterm_date) : null,
            finalDate: $course->final_date ? Date::parse($course->final_date) : null,
            examTimeStart: $course->exam_time_start,
            examTimeEnd: $course->exam_time_end,
            earliestExamAt: $dates->count() > 0 ? $dates->min() : null,
        );
    }

    public function formattedMidtermDate(): ?string
    {
        return $this->midtermDate?->isoFormat('M/D (dd)');
    }

    public function formattedFinalDate(): ?string
    {
        return $this->finalDate?->isoFormat('M/D (dd)');
    }

    public function formattedExamTime(): ?string
    {
        if (! $this->examTimeStart && ! $this->examTimeEnd) {
            return null;
        }

        if ($this->examTimeStart && $this->examTimeEnd) {
            return "{$this->examTimeStart} - {$this->examTimeEnd}";
        }

        return $this->examTimeStart ?? $this->examTimeEnd;
    }
}
