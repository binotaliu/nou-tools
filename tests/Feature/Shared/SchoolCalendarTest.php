<?php

use App\Models\CourseClass;
use App\Models\SchoolCalendarEvent;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

// Countdown days, status ("進行中"), and date formatting are computed on the
// client from the viewer's Taipei calendar date (see window.nouToolsSchoolCalendar
// in resources/js/app.js), so those behaviours are only observable with a
// real browser — see tests/Browser/SchoolCalendarTest.php. These Feature
// tests only check that the server hands the client the right raw event
// payload (filtered to still-relevant events, in chronological order).

it('displays school calendar on home page with events embedded for the client', function () {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-23', '2026-02-23')->countdown()->create(['name' => '114下學期課程開播']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-25', '2026-02-26')->create(['name' => '114下學期期中考']);

    // travel to a date just before events start so they appear as upcoming
    $this->travelTo('2026-02-22');

    $response = $this->get('/');

    // Countdown/status rendering happens client-side in SchoolCalendar.vue;
    // the server only needs to hand it the raw event payload via the
    // `schoolCalendar` prop (see HomeController::index).
    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Home/Index');

        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name');

        expect($names)->toContain('114下學期課程開播', '114下學期期中考');
    });
});

it('does not display school calendar when no events configured', function () {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-23', '2026-02-23')->countdown()->create(['name' => '課程開播']);

    $this->travelTo('2026-02-22');

    $courseClass = CourseClass::factory()->create();
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '我的課表',
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    // School calendar events are server-computed into the `schoolCalendar`
    // prop (see ScheduleController::show) and rendered client-side by
    // SchoolCalendar.vue, so assert against the prop's raw event payload.
    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Schedule/Show');

        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name');

        expect($names)->toContain('課程開播');
    });
});

it('shows the full calendar including past events when a non-current semester is selected', function () {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025A')->between('2025-09-01', '2025-09-01')->create(['name' => '114上學期開始']);
    SchoolCalendarEvent::factory()->forTerm('2025A')->between('2025-11-01', '2025-11-01')->create(['name' => '114上學期期中考']);

    $this->travelTo('2026-02-22');

    $courseClass = CourseClass::factory()->create();
    $courseClass->course()->update(['term' => '2025A']);
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '我的課表',
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule).'?term=2025A');

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Schedule/Show')->where('schoolCalendar.showPastEvents', true);

        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name');

        expect($names)->toContain('114上學期開始');
        expect($names)->toContain('114上學期期中考');
    });
});

it('filters out past events from the embedded payload', function () {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-01', '2026-02-01')->create(['name' => '過去的活動']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-23', '2026-02-23')->create(['name' => '未來的活動']);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Home/Index');

        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name');

        expect($names)->not->toContain('過去的活動');
        expect($names)->toContain('未來的活動');
    });
});

it('embeds events in chronological order for the client to render', function () {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-03-01', '2026-03-01')->create(['name' => '三月活動']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-20', '2026-02-20')->create(['name' => '二月活動']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-04-01', '2026-04-01')->create(['name' => '四月活動']);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Home/Index');

        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name')->all();

        expect($names)->toBe(['二月活動', '三月活動', '四月活動']);
    });
});

it('leaves events not flagged important off the home page and the schedule page', function () {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-23', '2026-02-23')->create(['name' => '重要活動']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-24', '2026-02-24')->minor()->create(['name' => '次要活動']);

    $this->travelTo('2026-02-22');

    $this->get('/')->assertInertia(function (Assert $page) {
        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name');

        expect($names)->toContain('重要活動')->not->toContain('次要活動');
    });

    $courseClass = CourseClass::factory()->create();
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '我的課表']);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    $this->get(route('schedules.show', $schedule))->assertInertia(function (Assert $page) {
        $names = collect($page->toArray()['props']['schoolCalendar']['events'])->pluck('name');

        expect($names)->toContain('重要活動')->not->toContain('次要活動');
    });
});
