<?php

namespace App\ViewModels;

use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;

class LearningProgressViewModel
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
        // pre-calculated progress metrics
        public int $completedCount = 0,
        public int $totalCount = 0,
        public float $percentage = 0.0,
    ) {}

    /**
     * 從 LearningProgress 模型和相關課程資訊建立 ViewModel
     */
    public static function fromModel(
        LearningProgress $learningProgress,
        StudentSchedule $schedule,
        array $courses,
        array $weeks,
        Carbon $semesterStart,
        Carbon $semesterEnd,
    ): self {
        $progressData = $learningProgress->progress ?? [];

        $total = count($courses) * count($weeks);
        $completed = 0;

        foreach ($courses as $course) {
            foreach ($weeks as $week) {
                $courseId = $course['id'];
                $weekNum = $week['num'];

                $slot = $progressData[$courseId][$weekNum] ?? [];

                if (($slot['video'] ?? false) && ($slot['textbook'] ?? false)) {
                    $completed++;
                }
            }
        }

        $percentage = $total > 0 ? ($completed / $total) * 100 : 0;

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
            percentage: $percentage,
        );
    }

    /**
     * 取得目前的週次（從 1 開始）
     */
    public function getCurrentWeek(): ?int
    {
        $now = $this->now->copy()->startOfDay();

        if ($now->lt($this->semesterStart) || $now->gt($this->semesterEnd)) {
            return null;
        }

        $diffDays = $now->diffInDays($this->semesterStart, absolute: true);

        return intdiv($diffDays, 7) + 1;
    }

    /**
     * 檢查某個週次是否已經過期（在目前週次之前）
     */
    public function isWeekPassed(int $weekNum): bool
    {
        $currentWeek = $this->getCurrentWeek();

        return $currentWeek !== null && $weekNum < $currentWeek;
    }

    /**
     * 檢查某課程在某週的進度是否完成
     */
    public function isProgressComplete(int $courseId, int $weekNum): bool
    {
        $weekProgress = $this->progress[$courseId][$weekNum] ?? [];

        return ($weekProgress['video'] ?? false)
            && ($weekProgress['textbook'] ?? false);
    }

    /**
     * 取得某課程在某週的備注
     */
    public function getNote(int $courseId, int $weekNum): string
    {
        return $this->notes[$courseId][$weekNum] ?? '';
    }
}
