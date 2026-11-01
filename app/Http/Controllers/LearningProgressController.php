<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\LearningProgress\Actions\ShowLearningProgressPage;
use NouTools\Domains\LearningProgress\Actions\UpdateLearningProgress;
use NouTools\Domains\LearningProgress\DataTransferObjects\UpdateLearningProgressData;

final class LearningProgressController extends Controller
{
    public function show(StudentSchedule $schedule, string $term, ShowLearningProgressPage $showLearningProgressPage): Response
    {
        $semesterCode = (string) config('app.current_semester');
        $range = config('app.current_semester_range', []);

        return Inertia::render('LearningProgress/Show', [
            'viewModel' => $showLearningProgressPage($schedule, $term),
            'greeting' => [
                'semesterLabel' => Str::toSemesterDisplay($semesterCode),
                'semesterCode' => $semesterCode,
                'semesterStart' => is_array($range) && ! empty($range[0]) ? (string) $range[0] : null,
                'semesterEnd' => is_array($range) && ! empty($range[1]) ? (string) $range[1] : null,
            ],
        ]);
    }

    public function update(UpdateLearningProgressData $input, StudentSchedule $schedule, string $term, UpdateLearningProgress $updateLearningProgress)
    {
        $result = $updateLearningProgress($schedule, $term, $input);

        $message = match (true) {
            $result['awarded'] > 0 => "學習進度已更新，獲得 {$result['awarded']} 顆橡實",
            $result['capped'] => '學習進度已更新，今日學習進度橡實已達上限',
            default => '學習進度已更新',
        };

        return redirect()
            ->route('learning-progress.show', [
                'schedule' => $schedule,
                'term' => $term,
            ])
            ->with('success', $message);
    }
}
