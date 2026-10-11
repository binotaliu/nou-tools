<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\Actions;

use App\Models\StudentSchedule;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\GradeCalculator\DataTransferObjects\UpdateGradeCalculatorData;
use NouTools\Domains\GradeCalculator\Support\IsSummerTerm;
use NouTools\Domains\GradeCalculator\Support\ScheduleTermCourses;

final class UpdateGradeCalculator
{
    public function __invoke(StudentSchedule $schedule, string $term, UpdateGradeCalculatorData $input): void
    {
        $calculation = $schedule->gradeCalculations()
            ->where('term', $term)
            ->firstOrFail();

        $courseIds = ScheduleTermCourses::for($schedule, $term)->modelKeys();

        $isSummer = IsSummerTerm::check($term);

        foreach (['scores', 'weights'] as $field) {
            foreach (array_keys($input->{$field}) as $courseId) {
                if (! in_array((int) $courseId, $courseIds, true)) {
                    throw ValidationException::withMessages([$field => '包含不在此課表的課程']);
                }
            }
        }

        foreach ($input->weights as $courseId => $weights) {
            if (abs($weights['regular'] + $weights['midterm'] + $weights['final'] - 100) > 0.001) {
                throw ValidationException::withMessages(["weights.{$courseId}" => '比例總和必須為 100%']);
            }

            if ($isSummer && (float) $weights['midterm'] !== 0.0) {
                throw ValidationException::withMessages(["weights.{$courseId}.midterm" => '暑期沒有期中考']);
            }
        }

        $calculation->fillFromDTO($input);
        $calculation->saveOrFail();
    }
}
