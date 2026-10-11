<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\GradeCalculator\Actions\ShowGradeCalculatorPage;
use NouTools\Domains\GradeCalculator\Actions\UpdateGradeCalculator;
use NouTools\Domains\GradeCalculator\DataTransferObjects\UpdateGradeCalculatorData;

final class GradeCalculatorController extends Controller
{
    public function show(StudentSchedule $schedule, string $term, ShowGradeCalculatorPage $showGradeCalculatorPage): Response
    {
        $semesterCode = (string) config('app.current_semester');
        $range = config('app.current_semester_range', []);

        return Inertia::render('GradeCalculator/Show', [
            'viewModel' => $showGradeCalculatorPage($schedule, $term),
            'greeting' => [
                'semesterLabel' => Str::toSemesterDisplay($semesterCode),
                'semesterCode' => $semesterCode,
                'semesterStart' => is_array($range) && ! empty($range[0]) ? (string) $range[0] : null,
                'semesterEnd' => is_array($range) && ! empty($range[1]) ? (string) $range[1] : null,
            ],
        ]);
    }

    public function update(UpdateGradeCalculatorData $input, StudentSchedule $schedule, string $term, UpdateGradeCalculator $updateGradeCalculator): RedirectResponse
    {
        $updateGradeCalculator($schedule, $term, $input);

        return redirect()
            ->route('grade-calculator.show', [
                'schedule' => $schedule,
                'term' => $term,
            ])
            ->with('success', '成績已儲存');
    }
}
