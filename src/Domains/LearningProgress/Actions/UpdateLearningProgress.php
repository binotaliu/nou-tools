<?php

namespace NouTools\Domains\LearningProgress\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\LearningProgress\DataTransferObjects\UpdateLearningProgressData;

final class UpdateLearningProgress
{
    public function __invoke(StudentSchedule $schedule, string $term, UpdateLearningProgressData $input): void
    {
        $learningProgress = $schedule->learningProgresses()
            ->where('term', $term)
            ->firstOrFail();

        $learningProgress->progress = $input->progress;
        $learningProgress->notes = $input->notes;
        $learningProgress->saveOrFail();
    }
}
