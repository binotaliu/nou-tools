<?php

declare(strict_types=1);

namespace NouTools\Domains\LearningProgress\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Actions\AwardLearningProgressAcorns;
use NouTools\Domains\LearningProgress\DataTransferObjects\UpdateLearningProgressData;

final readonly class UpdateLearningProgress
{
    public function __construct(private AwardLearningProgressAcorns $awardLearningProgressAcorns) {}

    /**
     * Saves the blobs and pays acorns for items that just became ticked.
     *
     * @return array{awarded: int, capped: bool}
     */
    public function __invoke(StudentSchedule $schedule, string $term, UpdateLearningProgressData $input): array
    {
        return DB::transaction(function () use ($schedule, $term, $input): array {
            $learningProgress = $schedule->learningProgresses()
                ->where('term', $term)
                ->firstOrFail();

            $before = [
                'progress' => $learningProgress->progress ?? [],
                'homework' => $learningProgress->homework ?? [],
            ];

            $learningProgress->progress = $input->progress;
            $learningProgress->notes = $input->notes;
            $learningProgress->homework = $input->homework;
            $learningProgress->saveOrFail();

            return ($this->awardLearningProgressAcorns)($schedule->id, $term, $before, [
                'progress' => $input->progress,
                'homework' => $input->homework,
            ]);
        });
    }
}
