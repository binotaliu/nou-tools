<?php

namespace NouTools\Domains\LearningProgress\ViewModels;

use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class LearningProgressViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $scheduleUuid,
        public ?string $scheduleName,
        public string $term,
        #[DataCollectionOf(LearningProgressCourseViewModel::class)]
        public DataCollection $courses,
        #[DataCollectionOf(LearningProgressWeekViewModel::class)]
        public DataCollection $weeks,
        public Carbon $semesterStart,
        public Carbon $semesterEnd,
        public Carbon $now,
        #[DataCollectionOf(LearningProgressEntryViewModel::class)]
        public DataCollection $entries,
        public int $completedCount = 0,
        public int $totalCount = 0,
        public float $percentage = 0.0,
    ) {}

    /**
     * @param  array<int, array{id: int, code?: ?string, name: string}>  $courses
     * @param  array<int, array{num: int, start: string, end: string}>  $weeks
     */
    public static function fromModel(LearningProgress $learningProgress, StudentSchedule $schedule, array $courses, array $weeks, Carbon $semesterStart, Carbon $semesterEnd): self
    {
        $progressData = $learningProgress->progress ?? [];
        $notesData = $learningProgress->notes ?? [];
        $total = count($courses) * count($weeks) * 2;
        $completed = 0;

        $entries = [];

        foreach ($courses as $course) {
            foreach ($weeks as $week) {
                $slot = $progressData[$course['id']][$week['num']] ?? [];
                $videoCompleted = (bool) ($slot['video'] ?? false);
                $textbookCompleted = (bool) ($slot['textbook'] ?? false);

                if ($videoCompleted) {
                    $completed++;
                }

                if ($textbookCompleted) {
                    $completed++;
                }

                $entries[] = new LearningProgressEntryViewModel(
                    courseId: $course['id'],
                    weekNum: $week['num'],
                    videoCompleted: $videoCompleted,
                    textbookCompleted: $textbookCompleted,
                    note: (string) ($notesData[$course['id']][$week['num']] ?? ''),
                );
            }
        }

        return new self(
            id: $learningProgress->id,
            scheduleUuid: $schedule->getRouteKey(),
            scheduleName: $schedule->name,
            term: $learningProgress->term,
            courses: new DataCollection(
                LearningProgressCourseViewModel::class,
                array_map(fn (array $course) => LearningProgressCourseViewModel::fromArray($course), $courses),
            ),
            weeks: new DataCollection(
                LearningProgressWeekViewModel::class,
                array_map(fn (array $week) => LearningProgressWeekViewModel::fromArray($week), $weeks),
            ),
            semesterStart: $semesterStart,
            semesterEnd: $semesterEnd,
            now: Carbon::now('Asia/Taipei'),
            entries: new DataCollection(LearningProgressEntryViewModel::class, $entries),
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

    public function findEntry(int $courseId, int $weekNum): ?LearningProgressEntryViewModel
    {
        return $this->entries->first(
            fn (LearningProgressEntryViewModel $entry) => $entry->courseId === $courseId && $entry->weekNum === $weekNum,
        );
    }

    public function isVideoComplete(int $courseId, int $weekNum): bool
    {
        return $this->findEntry($courseId, $weekNum)?->videoCompleted ?? false;
    }

    public function isTextbookComplete(int $courseId, int $weekNum): bool
    {
        return $this->findEntry($courseId, $weekNum)?->textbookCompleted ?? false;
    }

    public function isProgressComplete(int $courseId, int $weekNum): bool
    {
        return $this->isVideoComplete($courseId, $weekNum) && $this->isTextbookComplete($courseId, $weekNum);
    }

    public function getNote(int $courseId, int $weekNum): string
    {
        return $this->findEntry($courseId, $weekNum)?->note ?? '';
    }

    public function isWeekFullyComplete(int $weekNum): bool
    {
        return $this->courses->toCollection()->every(
            fn (LearningProgressCourseViewModel $course) => $this->isProgressComplete($course->id, $weekNum),
        );
    }

    public function hasIncompleteCourseInWeek(int $weekNum): bool
    {
        return $this->courses->toCollection()->contains(
            fn (LearningProgressCourseViewModel $course) => ! $this->isProgressComplete($course->id, $weekNum),
        );
    }
}
