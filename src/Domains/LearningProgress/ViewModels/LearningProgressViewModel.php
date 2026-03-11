<?php

namespace NouTools\Domains\LearningProgress\ViewModels;

use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;

final class LearningProgressViewModel
{
    public function __construct(
        public int $id,
        public string $scheduleUuid,
        public ?string $scheduleName,
        public string $term,
        public array $courses,
        public array $weeks,
        public Carbon $semesterStart,
        public Carbon $semesterEnd,
        public Carbon $now,
        public array $progress,
        public array $notes,
        public int $completedCount = 0,
        public int $totalCount = 0,
        public float $percentage = 0.0,
    ) {}

    public static function fromModel(LearningProgress $learningProgress, StudentSchedule $schedule, array $courses, array $weeks, Carbon $semesterStart, Carbon $semesterEnd): self
    {
        $progressData = $learningProgress->progress ?? [];
        $total = count($courses) * count($weeks) * 2;
        $completed = 0;

        foreach ($courses as $course) {
            foreach ($weeks as $week) {
                $slot = $progressData[$course['id']][$week['num']] ?? [];

                if (($slot['video'] ?? false)) {
                    $completed++;
                }

                if (($slot['textbook'] ?? false)) {
                    $completed++;
                }
            }
        }

        return new self(
            id: $learningProgress->id,
            scheduleUuid: $schedule->getRouteKey(),
            scheduleName: $schedule->name,
            term: $learningProgress->term,
            courses: $courses,
            weeks: $weeks,
            semesterStart: $semesterStart,
            semesterEnd: $semesterEnd,
            now: Carbon::now('Asia/Taipei'),
            progress: $progressData,
            notes: $learningProgress->notes ?? [],
            completedCount: $completed,
            totalCount: $total,
            percentage: $total > 0 ? ($completed / $total) * 100 : 0,
        );
    }

    public function getCurrentWeek(): ?int
    {
        $now = $this->now->copy()->startOfDay();

        if ($now->lt($this->semesterStart) || $now->gt($this->semesterEnd)) {
            return null;
        }

        return intdiv($now->diffInDays($this->semesterStart, absolute: true), 7) + 1;
    }

    public function isWeekPassed(int $weekNum): bool
    {
        $currentWeek = $this->getCurrentWeek();

        return $currentWeek !== null && $weekNum < $currentWeek;
    }

    public function isProgressComplete(int $courseId, int $weekNum): bool
    {
        $weekProgress = $this->progress[$courseId][$weekNum] ?? [];

        return ($weekProgress['video'] ?? false) && ($weekProgress['textbook'] ?? false);
    }

    public function getNote(int $courseId, int $weekNum): string
    {
        return $this->notes[$courseId][$weekNum] ?? '';
    }
}
