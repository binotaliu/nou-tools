<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;

// Saving goes through Inertia's useForm(), building progress/notes/homework
// from the page's v-model state rather than from the DOM, so only a real
// browser proves the payload matches what the controller stores.

it('saves ticked weeks, notes and homework without a full page load', function () {
    config()->set('app.current_semester', '2025B');
    config()->set('app.current_semester_range', ['2026-02-23', '2026-06-28']);

    $schedule = StudentSchedule::factory()->create();
    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state(['term' => '2025B']))
        ->create();

    $schedule->items()->create([
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    $learningProgress = LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
        'progress' => [],
        'notes' => [],
        'homework' => [],
    ]);

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']));

    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'#progress-form input[type="checkbox"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }

    $page->script("(() => {
        const box = document.querySelector('#progress-form input[type=\"checkbox\"]');
        box.click();
        const note = document.querySelector('#progress-form textarea');
        note.value = 'saved via inertia';
        note.dispatchEvent(new Event('input', { bubbles: true }));
    })()");

    $page->click('[data-testid="learning-progress-header"] button[data-analytics-event="learning_progress_save"]')
        ->assertSee('學習進度已更新');

    $stored = $learningProgress->refresh();

    expect(collect([$stored->progress, $stored->homework])->flatten()->filter()->isNotEmpty())->toBeTrue()
        ->and(json_encode([$stored->notes, $stored->homework]))->toContain('saved via inertia');
});
