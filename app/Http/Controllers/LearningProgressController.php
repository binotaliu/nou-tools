<?php

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\View\View;
use NouTools\Domains\LearningProgress\Actions\ShowLearningProgressPage;
use NouTools\Domains\LearningProgress\Actions\UpdateLearningProgress;
use NouTools\Domains\LearningProgress\DataTransferObjects\UpdateLearningProgressData;

class LearningProgressController extends Controller
{
    public function show(StudentSchedule $schedule, string $term, ShowLearningProgressPage $showLearningProgressPage): View
    {
        return view('learning-progress.show', ['viewModel' => $showLearningProgressPage($schedule, $term)]);
    }

    public function update(UpdateLearningProgressData $input, StudentSchedule $schedule, string $term, UpdateLearningProgress $updateLearningProgress)
    {
        $updateLearningProgress($schedule, $term, $input);

        return redirect()
            ->route('learning-progress.show', [
                'schedule' => $schedule,
                'term' => $term,
            ])
            ->with('success', '學習進度已更新');
    }
}
