<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;

// The 依週次/依科目 mobile views got a prev/next switch under their picker.
// Weeks clamp at the semester's edges; subjects wrap around and always show
// a target, so there's no disabled state to assert there.

afterEach(fn () => Carbon::setTestNow());

function dismissRememberModalIfPresentForNav($page): void
{
    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'[data-testid="learning-progress-view-switcher"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }
}

function scheduleWithTwoCourses(): StudentSchedule
{
    config()->set('app.current_semester', '2025B');
    config()->set('app.current_semester_range', ['2026-02-23', '2026-06-28']);

    $schedule = StudentSchedule::factory()->create();

    foreach (['國文賞析', '統計學'] as $name) {
        $courseClass = CourseClass::factory()
            ->for(Course::factory()->state(['term' => '2025B', 'name' => $name]))
            ->create();

        $schedule->items()->create([
            'course_id' => $courseClass->course_id,
            'course_class_id' => $courseClass->id,
        ]);
    }

    return $schedule;
}

it('clamps the week switch at the semester edges and offers a jump back to the current week', function () {
    Carbon::setTestNow('2026-02-23 09:00:00'); // the semester's first Monday: week 1

    $schedule = scheduleWithTwoCourses();

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->resize(390, 844);
    dismissRememberModalIfPresentForNav($page);

    $page->click('[data-testid="learning-progress-view-tab-week"]');

    expect($page->script(
        "document.querySelector('[data-testid=\"learning-progress-week-prev\"]').disabled"
    ))->toBeTrue();
    $page->assertMissing('[data-testid="learning-progress-week-current"]')
        ->assertSeeIn('[data-testid="learning-progress-week-next"]', '第二週');

    $page->click('[data-testid="learning-progress-week-next"]')
        ->assertSeeIn('[data-testid="learning-progress-week-current"]', '本週')
        ->assertSeeIn('[data-testid="learning-progress-week-prev"]', '第一週');

    $page->click('[data-testid="learning-progress-week-current"]')
        ->assertMissing('[data-testid="learning-progress-week-current"]');

    expect($page->script(
        "document.querySelector('[data-testid=\"learning-progress-week-prev\"]').disabled"
    ))->toBeTrue();
});

it('wraps the subject switch around and always shows a target name', function () {
    $schedule = scheduleWithTwoCourses();

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->resize(390, 844);
    dismissRememberModalIfPresentForNav($page);

    $page->click('[data-testid="learning-progress-view-tab-subject"]')
        ->assertSeeIn('[data-testid="learning-progress-subject-prev"]', '統計學')
        ->assertSeeIn('[data-testid="learning-progress-subject-next"]', '統計學');

    $page->click('[data-testid="learning-progress-subject-next"]')
        ->assertSeeIn('[data-testid="learning-progress-subject-prev"]', '國文賞析')
        ->assertSeeIn('[data-testid="learning-progress-subject-next"]', '國文賞析');
});
