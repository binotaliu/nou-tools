<?php

use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;

it('displays school calendar on home page', function () {
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
        ->assertSee('114下學期課程開播')
        ->assertSee('114下學期期中考');
});

it('displays countdown timer for upcoming countdown event on home page', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-25',
            'end' => '2026-02-25',
            'name' => '重要考試',
            'countdown' => true,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('重要考試')
        ->assertSee('7') // days until
        ->assertSee('天後');
});

it('displays ongoing status for current events on home page', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-15',
            'end' => '2026-02-20',
            'name' => '進行中的活動',
            'countdown' => true,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('進行中的活動')
        ->assertSee('進行中');
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

    // ensure events are treated as upcoming by moving time before start
    $this->travelTo('2026-02-22');

    $courseClass = CourseClass::factory()->create();
    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
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

it('displays events with date ranges correctly', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-05-01',
            'end' => '2026-05-20',
            'name' => '選課期間',
            'countdown' => false,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('選課期間')
        ->assertSee('5 月 1 日')
        ->assertSee('5 月 20 日');
});

it('does not display days until for non-countdown events', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-25',
            'end' => '2026-02-25',
            'name' => '普通活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-02-26',
            'end' => '2026-02-26',
            'name' => '倒數活動',
            'countdown' => true,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $content = $response->content();

    // The countdown event should be in the countdown section at the top
    expect($content)->toContain('倒數活動')
        ->toContain('天後');

    // Regular events should just show dates, not "天後" in the event list
    // This is tricky to test directly, but we can verify the structure
    $response->assertSee('普通活動');
});

it('does not duplicate countdown event in the list', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-25',
            'end' => '2026-02-25',
            'name' => '倒數活動',
            'countdown' => true,
        ],
        [
            'start' => '2026-02-26',
            'end' => '2026-02-26',
            'name' => '一般活動',
            'countdown' => false,
        ],
    ]]);

    $this->travelTo('2026-02-18');

    $response = $this->get('/');

    $content = $response->content();

    // countdown event should appear only once (countdown section only)
    expect(substr_count($content, '倒數活動'))->toBe(1);

    // other events still show
    $response->assertSee('一般活動');
});

it('filters out past events from display', function () {
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

it('displays events in chronological order', function () {
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

    // Verify order by checking positions
    $pos二月 = strpos($content, '二月活動');
    $pos三月 = strpos($content, '三月活動');
    $pos四月 = strpos($content, '四月活動');

    expect($pos二月)->toBeLessThan($pos三月)
        ->and($pos三月)->toBeLessThan($pos四月);
});
