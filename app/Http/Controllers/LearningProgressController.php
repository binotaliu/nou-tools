<?php

namespace App\Http\Controllers;

use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use App\ViewModels\LearningProgressViewModel;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LearningProgressController extends Controller
{
    /**
     * 顯示學習進度表
     */
    public function show(StudentSchedule $schedule, string $term): View
    {
        // 取得或建立該學期的學習進度
        $learningProgress = $schedule->learningProgresses()
            ->where('term', $term)
            ->first();

        if (! $learningProgress) {
            $learningProgress = $this->createLearningProgress($schedule, $term);
        }

        $schedule->load([
            'items' => fn (HasMany $q) => $q->whereHas('courseClass.course', fn (Builder $q) => $q->where('term', $term)),
            'items.courseClass.course' => fn (BelongsTo $q) => $q->where('term', $term),
        ]);

        // 取得該課表的所有課程（該學期）
        $courses = $schedule->items
            ->map(fn ($item) => $item->courseClass?->course)
            ->filter()
            ->values()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();

        // 計算學期的週次信息
        [$semesterStart, $semesterEnd, $weeks] = $this->calculateSemesterWeeks($term);

        // 建立 ViewModel
        $viewModel = LearningProgressViewModel::fromModel(
            $learningProgress,
            $schedule,
            $courses,
            $weeks,
            $semesterStart,
            $semesterEnd,
        );

        return view('learning-progress.show', ['viewModel' => $viewModel]);
    }

    /**
     * 更新學習進度
     */
    public function update(Request $request, StudentSchedule $schedule, string $term)
    {
        $learningProgress = $schedule->learningProgresses()
            ->where('term', $term)
            ->firstOrFail();

        $progress = $request->input('progress', []);
        $notes = $request->input('notes', []);

        $learningProgress->update([
            'progress' => $progress,
            'notes' => $notes,
        ]);

        return redirect()
            ->route('learning-progress.show', [
                'schedule' => $schedule,
                'term' => $term,
            ])
            ->with('success', '學習進度已更新');
    }

    /**
     * 為學期建立新的學習進度記錄
     */
    private function createLearningProgress(StudentSchedule $schedule, string $term): LearningProgress
    {
        return $schedule->learningProgresses()->create([
            'term' => $term,
            'progress' => [],
            'notes' => [],
        ]);
    }

    /**
     * 計算學期的週次信息
     *
     * @return array{Carbon, Carbon, array}
     */
    private function calculateSemesterWeeks(string $semesterCode): array
    {
        $range = config('app.current_semester_range', []);

        if (! is_array($range) || count($range) !== 2 || ! $range[0] || ! $range[1]) {
            throw new \RuntimeException("學期 {$semesterCode} 時間範圍未設定");
        }

        $semesterStart = Carbon::parse($range[0], 'Asia/Taipei')->startOfDay();
        $semesterEnd = Carbon::parse($range[1], 'Asia/Taipei')->endOfDay();

        // 計算總週數
        $totalDays = $semesterStart->diffInDays($semesterEnd);
        $totalWeeks = intdiv($totalDays, 7) + 1;

        $weeks = [];
        for ($i = 1; $i <= $totalWeeks; $i++) {
            $weekStart = $semesterStart->copy()->addDays(($i - 1) * 7);
            $weekEnd = $weekStart->copy()->addDays(6);

            // 如果超過學期結束日期，調整到學期結束日期
            if ($weekEnd->gt($semesterEnd)) {
                $weekEnd = $semesterEnd;
            }

            $weeks[] = [
                'num' => $i,
                'start' => $weekStart->isoFormat('M/D'),
                'end' => $weekEnd->isoFormat('M/D'),
            ];
        }

        return [$semesterStart, $semesterEnd, $weeks];
    }
}
