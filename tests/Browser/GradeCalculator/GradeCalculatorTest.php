<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\GradeCalculation;
use App\Models\StudentSchedule;

// The maths runs in the browser (useGradeCalculation) and saving goes through
// Inertia's useForm(), so only a real browser proves both match the server.

$gradeCalculatorSchedule = function (string $term): array {
    config()->set('app.current_semester', $term);
    config()->set('app.current_semester_range', ['2026-02-23', '2026-06-28']);

    $schedule = StudentSchedule::factory()->create();
    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state(['term' => $term]))
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    return [$schedule, $courseClass->course];
};

$openGradeCalculator = function (StudentSchedule $schedule, string $term): mixed {
    $page = visit(route('grade-calculator.show', ['schedule' => $schedule, 'term' => $term]));

    waitUntil($page, 'document.querySelector(\'[data-testid^="grade-course-"]\') !== null');

    return $page;
};

$typeInto = function (mixed $page, string $testId, string $value): void {
    $page->script("(() => {
        const input = document.querySelector('[data-testid=\"{$testId}\"]');
        input.value = '{$value}';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    })()");
};

$resultText = function (mixed $page, int $courseId): string {
    return (string) $page->script("document.querySelector('[data-testid=\"result-{$courseId}\"]').textContent.replace(/\\s+/g, ' ').trim()");
};

it('calculates the semester score with the default 30/30/40 weights', function () use ($gradeCalculatorSchedule, $openGradeCalculator, $typeInto, $resultText) {
    [$schedule, $course] = $gradeCalculatorSchedule('2025B');
    $page = $openGradeCalculator($schedule, '2025B');

    $typeInto($page, "score-{$course->id}-regular", '80');
    $typeInto($page, "score-{$course->id}-midterm", '70');
    $typeInto($page, "score-{$course->id}-final", '60');

    waitUntil($page, "document.querySelector('[data-testid=\"result-{$course->id}\"]').textContent.includes('69')");

    // 80*.3 + 70*.3 + 60*.4 = 69
    expect($resultText($page, $course->id))->toContain('學期成績 69 分')->toContain('及格');
});

it('shows the final score needed to pass while the final is empty', function () use ($gradeCalculatorSchedule, $openGradeCalculator, $typeInto, $resultText) {
    [$schedule, $course] = $gradeCalculatorSchedule('2025B');
    $page = $openGradeCalculator($schedule, '2025B');

    $typeInto($page, "score-{$course->id}-regular", '50');
    $typeInto($page, "score-{$course->id}-midterm", '50');

    // (60 - 50*.3 - 50*.3) / .4 = 75
    waitUntil($page, "document.querySelector('[data-testid=\"result-{$course->id}\"]').textContent.includes('至少要考')");
    expect($resultText($page, $course->id))->toContain('75');

    $typeInto($page, "score-{$course->id}-regular", '0');
    $typeInto($page, "score-{$course->id}-midterm", '0');
    waitUntil($page, "document.querySelector('[data-testid=\"result-{$course->id}\"]').textContent.includes('無法及格')");

    $typeInto($page, "score-{$course->id}-regular", '100');
    $typeInto($page, "score-{$course->id}-midterm", '100');
    waitUntil($page, "document.querySelector('[data-testid=\"result-{$course->id}\"]').textContent.includes('已達')");
});

it('drops the midterm in summer and weights the final 70%', function () use ($gradeCalculatorSchedule, $openGradeCalculator, $typeInto) {
    [$schedule, $course] = $gradeCalculatorSchedule('2026C');
    $page = $openGradeCalculator($schedule, '2026C');

    expect($page->script("document.querySelector('[data-testid=\"score-{$course->id}-midterm\"]')"))->toBeNull();

    $typeInto($page, "score-{$course->id}-regular", '60');
    $typeInto($page, "score-{$course->id}-final", '80');

    // 60*.3 + 80*.7 = 74
    waitUntil($page, "document.querySelector('[data-testid=\"result-{$course->id}\"]').textContent.includes('74')");
});

it('recalculates with custom weights and blocks saving when they do not sum to 100', function () use ($gradeCalculatorSchedule, $openGradeCalculator, $typeInto) {
    [$schedule, $course] = $gradeCalculatorSchedule('2025B');
    $page = $openGradeCalculator($schedule, '2025B');

    $typeInto($page, "score-{$course->id}-regular", '100');
    $typeInto($page, "score-{$course->id}-midterm", '0');
    $typeInto($page, "score-{$course->id}-final", '0');
    $typeInto($page, "weight-{$course->id}-regular", '50');

    waitUntil($page, "document.querySelector('[data-testid=\"weight-error-{$course->id}\"]') !== null");
    expect($page->script("document.querySelector('[data-testid=\"grade-calculator-save\"]').disabled"))->toBeTrue();

    $typeInto($page, "weight-{$course->id}-final", '20');
    waitUntil($page, "document.querySelector('[data-testid=\"result-{$course->id}\"]').textContent.includes('50')");
    expect($page->script("document.querySelector('[data-testid=\"grade-calculator-save\"]').disabled"))->toBeFalse();
});

it('saves scores and weights and shows them again after a reload', function () use ($gradeCalculatorSchedule, $openGradeCalculator, $typeInto) {
    [$schedule, $course] = $gradeCalculatorSchedule('2025B');
    $page = $openGradeCalculator($schedule, '2025B');

    $typeInto($page, "score-{$course->id}-regular", '88');
    $typeInto($page, "weight-{$course->id}-regular", '40');
    $typeInto($page, "weight-{$course->id}-midterm", '20');

    $page->click('[data-testid="grade-calculator-save"]')->assertSee('成績已儲存');

    $stored = GradeCalculation::firstOrFail();

    expect((float) $stored->scores[$course->id]['regular'])->toBe(88.0)
        ->and($stored->scores[$course->id]['final'])->toBeNull()
        ->and((float) $stored->weights[$course->id]['regular'])->toBe(40.0)
        ->and((float) $stored->weights[$course->id]['midterm'])->toBe(20.0);

    $reloaded = $openGradeCalculator($schedule, '2025B');

    expect($reloaded->script("document.querySelector('[data-testid=\"score-{$course->id}-regular\"]').value"))->toBe('88');
});
