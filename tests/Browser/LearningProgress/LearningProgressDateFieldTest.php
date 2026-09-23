<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Pest\Browser\Api\PendingAwaitablePage;

// The homework deadline is a custom picker rather than `<input type="date">`
// (whose format differs per browser and which Safari fills with today's date
// when empty). Its label, popover and hidden value are all client-side, so a
// server-rendered Feature test can't tell whether they actually work.

function dismissRememberModalIfPresentForDateField(PendingAwaitablePage $page): void
{
    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'[data-testid="date-field-trigger"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }
}

function learningProgressWithDeadline(?string $deadline): StudentSchedule
{
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

    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
        'homework' => [
            $courseClass->course_id => [1 => ['deadline' => $deadline, 'note' => '', 'completed' => false]],
        ],
    ]);

    return $schedule;
}

const DATE_FIELD = '[data-testid="date-field-trigger"][aria-label*="作業一"]';
const DATE_FIELD_VALUE = 'document.querySelector(\'input[name$="[1][deadline]"]\').value';

it('shows a fixed M/D label and a placeholder instead of a native date input', function () {
    $schedule = learningProgressWithDeadline('2026-03-05');

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']));
    dismissRememberModalIfPresentForDateField($page);

    $page->assertSeeIn(DATE_FIELD, '3/5')
        ->assertMissing('input[type="date"]');

    expect($page->script(DATE_FIELD_VALUE))->toBe('2026-03-05');
});

it('renders an unset deadline as a placeholder, not today', function () {
    $schedule = learningProgressWithDeadline(null);

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']));
    dismissRememberModalIfPresentForDateField($page);

    $page->assertSeeIn(DATE_FIELD, '未設定');

    expect($page->script(DATE_FIELD_VALUE))->toBe('');
});

it('picks, changes and clears a deadline from the calendar', function () {
    $schedule = learningProgressWithDeadline('2026-03-05');

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']));
    dismissRememberModalIfPresentForDateField($page);

    $page->click(DATE_FIELD)
        ->assertSeeIn('[data-testid="date-field-popover"]', '2026 年 3 月')
        ->click('[data-testid="date-field-popover"] [data-date="2026-03-12"]')
        ->assertMissing('[data-testid="date-field-popover"]')
        ->assertSeeIn(DATE_FIELD, '3/12');

    expect($page->script(DATE_FIELD_VALUE))->toBe('2026-03-12');

    $page->click(DATE_FIELD)
        ->click('[data-testid="date-field-next"]')
        ->assertSeeIn('[data-testid="date-field-popover"]', '2026 年 4 月')
        ->click('[data-testid="date-field-popover"] [data-date="2026-04-01"]')
        ->assertSeeIn(DATE_FIELD, '4/1');

    expect($page->script(DATE_FIELD_VALUE))->toBe('2026-04-01');

    $page->click(DATE_FIELD)
        ->click('[data-testid="date-field-clear"]')
        ->assertSeeIn(DATE_FIELD, '未設定');

    expect($page->script(DATE_FIELD_VALUE))->toBe('');
});

it('starts the calendar week on Monday', function () {
    $schedule = learningProgressWithDeadline('2026-03-05');

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']));
    dismissRememberModalIfPresentForDateField($page);

    $page->click(DATE_FIELD);

    // March 2026 starts on a Sunday, so with a Monday-first grid it is the
    // seventh cell (6 leading blanks), and 2026-03-02 (Monday) starts row two.
    expect($page->script(
        "[...document.querySelectorAll('[data-testid=\"date-field-popover\"] .grid')][1].children[6].dataset.date"
    ))->toBe('2026-03-01');
    expect($page->script(
        "[...document.querySelectorAll('[data-testid=\"date-field-popover\"] .grid')][0].children[0].textContent.trim()"
    ))->toBe('一');
});
