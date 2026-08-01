<?php

use NouTools\Domains\Shared\SchoolCalendar\Actions\ListUpcomingSchoolEvents;

// Status/daysUntil/which-event-is-the-countdown used to be computed here,
// but now happen client-side (see window.nouToolsSchoolCalendar in
// resources/js/app.js) so the calendar stays anchored to Asia/Taipei from
// the viewer's own clock. This action's only remaining job is handing the
// client raw, still-relevant events.

beforeEach(function () {
    $this->service = new ListUpcomingSchoolEvents;
});

it('returns empty array when no schedules configured', function () {
    config(['app.current_semester' => '2025A']);
    config(['school-schedules.2025A' => []]);

    $events = ($this->service)();

    expect($events)->toBeArray()->toBeEmpty();
});

it('filters out past events', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-01-01',
            'end' => '2026-01-01',
            'name' => '過去的活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)('2026-02-18');

    expect($events)->toBeEmpty();
});

it('includes an event ending today as plain Y-m-d strings', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-15',
            'end' => '2026-02-18',
            'name' => '進行中的活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)('2026-02-18');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('進行中的活動')
        ->and($events[0]['start'])->toBe('2026-02-15')
        ->and($events[0]['end'])->toBe('2026-02-18')
        ->and($events[0]['countdown'])->toBeFalse();
});

it('includes upcoming events', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-25',
            'end' => '2026-02-26',
            'name' => '即將到來的活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)('2026-02-18');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('即將到來的活動')
        ->and($events[0]['start'])->toBe('2026-02-25')
        ->and($events[0]['end'])->toBe('2026-02-26');
});

it('sorts events by start date', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-03-01',
            'end' => '2026-03-01',
            'name' => '第三個活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-02-20',
            'end' => '2026-02-20',
            'name' => '第一個活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-02-25',
            'end' => '2026-02-25',
            'name' => '第二個活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)('2026-02-18');

    expect($events)->toHaveCount(3)
        ->and($events[0]['name'])->toBe('第一個活動')
        ->and($events[1]['name'])->toBe('第二個活動')
        ->and($events[2]['name'])->toBe('第三個活動');
});

it('preserves the countdown flag from config for the client to select', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-20',
            'end' => '2026-02-20',
            'name' => '不倒數的活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-02-23',
            'end' => '2026-02-23',
            'name' => '需要倒數的活動',
            'countdown' => true,
        ],
    ]]);

    $events = ($this->service)('2026-02-18');

    expect($events)->toHaveCount(2)
        ->and($events[0]['countdown'])->toBeFalse()
        ->and($events[1]['countdown'])->toBeTrue();
});

it('handles multi-day events correctly', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-05-01',
            'end' => '2026-05-20',
            'name' => '多日活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)('2026-02-18');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('多日活動')
        ->and($events[0]['start'])->toBe('2026-05-01')
        ->and($events[0]['end'])->toBe('2026-05-20');
});

it('uses current date when no reference date provided', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2030-01-01',
            'end' => '2030-01-01',
            'name' => '未來活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)();

    expect($events)->toHaveCount(1);
});

it('includes past events when a non-current semester is explicitly requested', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025A' => [
        [
            'start' => '2025-09-01',
            'end' => '2025-09-01',
            'name' => '已結束的活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-01-05',
            'end' => '2026-01-06',
            'name' => '也已結束的活動',
            'countdown' => true,
        ],
    ]]);

    $events = ($this->service)('2026-02-18', '2025A');

    expect($events)->toHaveCount(2)
        ->and($events[0]['name'])->toBe('已結束的活動')
        ->and($events[1]['name'])->toBe('也已結束的活動');
});

it('still filters out past events when the requested term is the current semester', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-01-01',
            'end' => '2026-01-01',
            'name' => '過去的活動',
            'countdown' => false,
        ],
        [
            'start' => '2026-03-01',
            'end' => '2026-03-01',
            'name' => '未來的活動',
            'countdown' => false,
        ],
    ]]);

    $events = ($this->service)('2026-02-18', '2025B');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('未來的活動');
});
