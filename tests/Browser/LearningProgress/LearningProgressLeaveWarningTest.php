<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;

// Both the native `beforeunload` prompt and Inertia's in-app navigation have to
// be guarded, and only a real browser exercises either.

$openLearningProgress = function () {
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

    LearningProgress::factory()->create(['student_schedule_id' => $schedule->id, 'term' => '2025B']);

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']));

    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'#progress-form input[type="checkbox"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }

    return $page;
};

const TICK_FIRST_BOX = "document.querySelector('#progress-form input[type=\"checkbox\"]').click()";
const BEFOREUNLOAD_PREVENTED = "(() => { const e = new Event('beforeunload', { cancelable: true }); window.dispatchEvent(e); return e.defaultPrevented })()";

it('does not warn on a pristine page', function () use ($openLearningProgress) {
    $page = $openLearningProgress();

    expect($page->script(BEFOREUNLOAD_PREVENTED))->toBeFalse();
});

it('warns on native unload and on Inertia navigation while edits are unsaved', function () use ($openLearningProgress) {
    $page = $openLearningProgress();

    $page->script(TICK_FIRST_BOX);

    expect($page->script(BEFOREUNLOAD_PREVENTED))->toBeTrue();

    // Decline the confirm: the visit is cancelled and we stay on the page.
    $page->script('window.__confirmed = []; window.confirm = m => { window.__confirmed.push(m); return false }');
    $page->script("(() => { const l = document.querySelector('a[href=\"/announcements\"]'); window.__found = !!l; l && l.click() })()");

    waitUntil($page, "window.__confirmed.includes('你有尚未儲存的變更，確定要離開嗎？')");

    expect($page->script('location.pathname'))->toContain('learning-progress');
});

it('stops warning once the changes are saved', function () use ($openLearningProgress) {
    $page = $openLearningProgress();

    $page->script(TICK_FIRST_BOX);
    $page->click('[data-testid="learning-progress-header"] button[data-analytics-event="learning_progress_save"]')
        ->assertSee('學習進度已更新');

    expect($page->script(BEFOREUNLOAD_PREVENTED))->toBeFalse();
});
