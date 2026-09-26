<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// Every data table must be announced by screen readers with a name, column
// and row headers that carry a scope, and no empty header cells.

const TABLE_AUDIT_SCRIPT = <<<'JS'
JSON.stringify([...document.querySelectorAll('table')].flatMap(t => {
    const problems = [];
    const named = (t.caption && t.caption.textContent.trim()) || t.getAttribute('aria-label') || t.getAttribute('aria-labelledby');
    if (!named) { problems.push('table without a name'); }
    if (t.getAttribute('role') === 'presentation') { return []; }
    t.querySelectorAll('th').forEach(th => {
        if (!th.getAttribute('scope')) { problems.push('th without scope: ' + th.textContent.trim().slice(0, 20)); }
        if (!th.textContent.trim() && !th.getAttribute('aria-label')) { problems.push('empty th'); }
    });
    return problems;
}))
JS;

$expectAccessibleTables = function ($page): int {
    $problems = json_decode($page->script(TABLE_AUDIT_SCRIPT), true);
    expect($problems)->toBe([]);

    return (int) $page->script("document.querySelectorAll('table').length");
};

it('names every table and scopes every header on the accessibility page', function () use ($expectAccessibleTables) {
    $page = visit('/accessibility');
    $page->assertNoJavaScriptErrors();

    expect($expectAccessibleTables($page))->toBeGreaterThan(0);
});

it('names every table and scopes every header on the schedule and learning progress pages', function () use ($expectAccessibleTables) {
    config()->set('app.current_semester', '2025B');
    config()->set('app.current_semester_range', ['2026-02-23', '2026-06-28']);

    $course = Course::factory()->create(['term' => '2025B', 'midterm_date' => now()->addWeek(), 'final_date' => now()->addWeeks(3)]);
    $courseClass = CourseClass::factory()->create(['course_id' => $course->id, 'code' => 'TBL101']);
    ClassSchedule::factory()->create(['class_id' => $courseClass->id, 'date' => now()->addDays(2)->toDateString()]);

    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Table audit']);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    foreach ([
        route('schedules.show', ['schedule' => $schedule, 'term' => '2025B']),
        route('learning-progress.show', ['schedule' => $schedule, 'term' => '2025B']),
    ] as $url) {
        $page = visit($url);
        $page->assertNoJavaScriptErrors();
        waitUntil($page, "document.querySelector('table') !== null");

        expect($expectAccessibleTables($page))->toBeGreaterThan(0);
    }
});

it('names every table and scopes every header on the course schedule page', function () use ($expectAccessibleTables) {
    Course::factory()->create(['term' => config('app.current_semester'), 'final_date' => now()->next('Saturday')]);

    $page = visit(route('course.schedule'));
    $page->assertNoJavaScriptErrors();
    waitUntil($page, "document.querySelector('table') !== null");

    expect($expectAccessibleTables($page))->toBeGreaterThan(0);
});
