<?php

namespace NouTools\Domains\LearningProgress\Actions;

use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use NouTools\Domains\LearningProgress\ViewModels\LearningProgressViewModel;

final class ShowLearningProgressPage
{
    public function __invoke(StudentSchedule $schedule, string $term): LearningProgressViewModel
    {
        $hasCourses = $schedule->items()
            ->whereHas('courseClass.course', fn (Builder $query) => $query->where('term', $term))
            ->exists();

        abort_if(! $hasCourses, 404);

        $learningProgress = $schedule->learningProgresses()
            ->where('term', $term)
            ->first() ?? $this->createLearningProgress($schedule, $term);

        $schedule->load([
            'items' => fn (HasMany $query) => $query->whereHas('courseClass.course', fn (Builder $courseQuery) => $courseQuery->where('term', $term)),
            'items.courseClass.course' => fn (BelongsTo $query) => $query->where('term', $term),
        ]);

        $courses = $schedule->items
            ->map(fn ($item) => $item->courseClass?->course)
            ->filter()
            ->values()
            ->map(fn ($course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
            ])
            ->unique('id')
            ->values()
            ->toArray();

        abort_if(empty($courses), 404);

        [$semesterStart, $semesterEnd, $weeks] = $this->calculateSemesterWeeks($term);

        return LearningProgressViewModel::fromModel(
            learningProgress: $learningProgress,
            schedule: $schedule,
            courses: $courses,
            weeks: $weeks,
            semesterStart: $semesterStart,
            semesterEnd: $semesterEnd,
        );
    }

    private function createLearningProgress(StudentSchedule $schedule, string $term): LearningProgress
    {
        return $schedule->learningProgresses()->create([
            'term' => $term,
            'progress' => [],
            'notes' => [],
        ]);
    }

    private function calculateSemesterWeeks(string $semesterCode): array
    {
        $range = config('app.current_semester_range', []);

        if (! is_array($range) || count($range) !== 2 || ! $range[0] || ! $range[1]) {
            throw new \RuntimeException("學期 {$semesterCode} 時間範圍未設定");
        }

        $semesterStart = Carbon::parse($range[0], 'Asia/Taipei')->startOfDay();
        $semesterEnd = Carbon::parse($range[1], 'Asia/Taipei')->endOfDay();
        $totalWeeks = intdiv($semesterStart->diffInDays($semesterEnd), 7) + 1;
        $weeks = [];

        for ($weekNumber = 1; $weekNumber <= $totalWeeks; $weekNumber++) {
            $weekStart = $semesterStart->copy()->addDays(($weekNumber - 1) * 7);
            $weekEnd = $weekStart->copy()->addDays(6);

            if ($weekEnd->gt($semesterEnd)) {
                $weekEnd = $semesterEnd;
            }

            $weeks[] = [
                'num' => $weekNumber,
                'start' => $weekStart->isoFormat('M/D'),
                'end' => $weekEnd->isoFormat('M/D'),
            ];
        }

        return [$semesterStart, $semesterEnd, $weeks];
    }
}
