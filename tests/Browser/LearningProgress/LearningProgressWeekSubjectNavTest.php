<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use Illuminate\Support\Carbon;

// The 依週次/依科目 mobile views got a prev/next switch under their picker.
// Weeks clamp at the semester's edges; subjects wrap around and always show
// a target, so there's no disabled state to assert there.

afterEach(fn () => Carbon::setTestNow());

$dismissRememberModalIfPresentForNav = function ($page): void {
    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'[data-testid="learning-progress-view-switcher"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }
};

$scheduleWithTwoCourses = function (): StudentSchedule {
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
};

it('clamps the week switch at the semester edges and offers a jump back to the current week', function () use ($dismissRememberModalIfPresentForNav, $scheduleWithTwoCourses) {
    Carbon::setTestNow('2026-02-23 09:00:00'); // the semester's first Monday: week 1

    $schedule = $scheduleWithTwoCourses();

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->resize(390, 844);
    $dismissRememberModalIfPresentForNav($page);

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

it('wraps the subject switch around and always shows a target name', function () use ($dismissRememberModalIfPresentForNav, $scheduleWithTwoCourses) {
    $schedule = $scheduleWithTwoCourses();

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->resize(390, 844);
    $dismissRememberModalIfPresentForNav($page);

    $page->click('[data-testid="learning-progress-view-tab-subject"]')
        ->assertSeeIn('[data-testid="learning-progress-subject-prev"]', '統計學')
        ->assertSeeIn('[data-testid="learning-progress-subject-next"]', '統計學');

    $page->click('[data-testid="learning-progress-subject-next"]')
        ->assertSeeIn('[data-testid="learning-progress-subject-prev"]', '國文賞析')
        ->assertSeeIn('[data-testid="learning-progress-subject-next"]', '國文賞析');
});

it('lays the week and subject views out as boards on desktop and one track at a time on a phone', function () use ($dismissRememberModalIfPresentForNav, $scheduleWithTwoCourses) {
    $schedule = $scheduleWithTwoCourses();

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']))
        ->resize(1440, 900);
    $dismissRememberModalIfPresentForNav($page);

    // Desktop keeps the table as its default; the switcher is there too.
    $page->assertVisible('[data-testid="learning-progress-view-switcher"]')
        ->assertMissing('[data-testid="learning-progress-week-view"]');

    $visibleCount = fn (string $testId) => $page->script(
        "[...document.querySelectorAll('[data-testid=\"{$testId}\"]')].filter(el => el.offsetParent !== null).length"
    );

    $page->click('[data-testid="learning-progress-view-tab-subject"]');
    expect($visibleCount('learning-progress-subject-column'))->toBe(2);
    $page->assertMissing('[data-testid="learning-progress-subject-picker"]');

    $page->click('[data-testid="learning-progress-view-tab-week"]');
    expect($visibleCount('learning-progress-week-column'))->toBeGreaterThan(2);
    $page->assertMissing('[data-testid="learning-progress-week-picker"]');

    $page->resize(390, 844)
        ->assertVisible('[data-testid="learning-progress-week-picker"]');
    expect($visibleCount('learning-progress-week-column'))->toBe(1);

    $page->click('[data-testid="learning-progress-view-tab-subject"]');
    expect($visibleCount('learning-progress-subject-column'))->toBe(1);
});
