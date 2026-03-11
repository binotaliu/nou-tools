<?php

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\Request;
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

    public function update(Request $request, StudentSchedule $schedule, string $term, UpdateLearningProgress $updateLearningProgress)
    {
        $validated = $request->validate(UpdateLearningProgressData::rules());
        $updateLearningProgress($schedule, $term, UpdateLearningProgressData::from($validated));

        return redirect()
            ->route('learning-progress.show', [
                'schedule' => $schedule,
                'term' => $term,
            ])
            ->with('success', '學習進度已更新');
    }
}
