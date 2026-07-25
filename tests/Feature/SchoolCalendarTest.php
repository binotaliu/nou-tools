<?php

use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// Countdown days, status ("進行中"), and date formatting are computed on the
// client from the viewer's Taipei calendar date (see window.nouSchoolCalendar
// in resources/js/app.js), so those behaviours are only observable with a
// real browser — see tests/Browser/SchoolCalendarTest.php. These Feature
// tests only check that the server hands the client the right raw event
// payload (filtered to still-relevant events, in chronological order).

it('displays school calendar on home page with events embedded for the client', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-23',
            'end' => '2026-02-23',
            'name' => '114下學期課程開播',
            'countdown' => true,
        ],
        [
            'start' => '2026-02-25',
            'end' => '2026-02-26',
            'name' => '114下學期期中考',
            'countdown' => false,
        ],
    ]]);

    // travel to a date just before events start so they appear as upcoming
    $this->travelTo('2026-02-22');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('學校行事曆')
        ->assertSee('nouSchoolCalendar(', false)
        ->assertSee('114下學期課程開播')
        ->assertSee('114下學期期中考');
});

it('does not display school calendar when no events configured', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => []]);

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertDontSee('學校行事曆');
});

it('displays school calendar on schedule show page', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-23',
            'end' => '2026-02-23',
            'name' => '課程開播',
            'countdown' => true,
        ],
    ]]);

    $this->travelTo('2026-02-22');

    $courseClass = CourseClass::factory()->create();
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '我的課表',
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee('學校行事曆')
        ->assertSee('課程開播');
});

it('filters out past events from the embedded payload', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-01',
            'end' => '2026-02-01',
            'name' => '過去的活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-02-23',
            'end' => '2026-02-23',
            'name' => '未來的活動',
            'countdown' => false,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertDontSee('過去的活動')
        ->assertSee('未來的活動');
});

it('embeds events in chronological order for the client to render', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-03-01',
            'end' => '2026-03-01',
            'name' => '三月活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-02-20',
            'end' => '2026-02-20',
            'name' => '二月活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-04-01',
            'end' => '2026-04-01',
            'name' => '四月活動',
            'countdown' => false,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $content = $response->content();

    $pos二月 = strpos($content, '二月活動');
    $pos三月 = strpos($content, '三月活動');
    $pos四月 = strpos($content, '四月活動');

    expect($pos二月)->toBeLessThan($pos三月)
        ->and($pos三月)->toBeLessThan($pos四月);
});
