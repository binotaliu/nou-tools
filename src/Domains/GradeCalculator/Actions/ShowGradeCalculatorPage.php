<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\Actions;

use App\Models\Course;
use App\Models\StudentSchedule;
use NouTools\Domains\GradeCalculator\Support\DefaultWeights;
use NouTools\Domains\GradeCalculator\Support\IsSummerTerm;
use NouTools\Domains\GradeCalculator\Support\ScheduleTermCourses;
use NouTools\Domains\GradeCalculator\ViewModels\GradeCalculatorCourseViewModel;
use NouTools\Domains\GradeCalculator\ViewModels\GradeCalculatorViewModel;

final class ShowGradeCalculatorPage
{
    public function __invoke(StudentSchedule $schedule, string $term): GradeCalculatorViewModel
    {
        $courses = ScheduleTermCourses::for($schedule, $term);

        abort_if($courses->isEmpty(), 404);

        $calculation = $schedule->gradeCalculations()->where('term', $term)->first();

        if ($calculation === null) {
            $calculation = $schedule->gradeCalculations()->make();
            $calculation->term = $term;
            $calculation->scores = [];
            $calculation->weights = [];
            $calculation->saveOrFail();
        }

        $isSummer = IsSummerTerm::check($term);
        $defaults = DefaultWeights::for($isSummer);

        return new GradeCalculatorViewModel(
            scheduleUuid: $schedule->getRouteKey(),
            scheduleName: $schedule->name,
            term: $term,
            isSummer: $isSummer,
            defaultWeights: $defaults,
            courses: $courses->map(fn (Course $course) => new GradeCalculatorCourseViewModel(
                id: $course->id,
                name: $course->name,
                credits: $course->credits,
                scores: [
                    'regular' => self::number($calculation->scores[$course->id]['regular'] ?? null),
                    'midterm' => self::number($calculation->scores[$course->id]['midterm'] ?? null),
                    'final' => self::number($calculation->scores[$course->id]['final'] ?? null),
                ],
                weights: $isSummer ? $defaults : [
                    'regular' => (float) ($calculation->weights[$course->id]['regular'] ?? $defaults['regular']),
                    'midterm' => (float) ($calculation->weights[$course->id]['midterm'] ?? $defaults['midterm']),
                    'final' => (float) ($calculation->weights[$course->id]['final'] ?? $defaults['final']),
                ],
            ))->all(),
        );
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
