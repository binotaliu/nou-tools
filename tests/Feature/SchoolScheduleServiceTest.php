<?php

use App\Services\SchoolScheduleService;

beforeEach(function () {
    $this->service = new SchoolScheduleService;
});

it('returns empty array when no schedules configured', function () {
    config(['app.current_semester' => '2025A']);
    config(['school-schedules.2025A' => []]);

    $events = $this->service->getUpcomingAndOngoingEvents();

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

    $events = $this->service->getUpcomingAndOngoingEvents('2026-02-18');

    expect($events)->toBeEmpty();
});

it('includes ongoing events', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-15',
            'end' => '2026-02-20',
            'name' => '進行中的活動',
            'countdown' => false,
        ],
    ]]);

    $events = $this->service->getUpcomingAndOngoingEvents('2026-02-18');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('進行中的活動')
        ->and($events[0]['status'])->toBe('ongoing')
        ->and($events[0]['daysUntil'])->toBe(0);
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

    $events = $this->service->getUpcomingAndOngoingEvents('2026-02-18');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('即將到來的活動')
        ->and($events[0]['status'])->toBe('upcoming')
        ->and($events[0]['daysUntil'])->toBe(7);
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

    $events = $this->service->getUpcomingAndOngoingEvents('2026-02-18');

    expect($events)->toHaveCount(3)
        ->and($events[0]['name'])->toBe('第一個活動')
        ->and($events[1]['name'])->toBe('第二個活動')
        ->and($events[2]['name'])->toBe('第三個活動');
});

it('returns countdown event when one exists', function () {
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

    $countdownEvent = $this->service->getCountdownEvent('2026-02-18');

    expect($countdownEvent)->not->toBeNull()
        ->and($countdownEvent['name'])->toBe('需要倒數的活動')
        ->and($countdownEvent['countdown'])->toBeTrue()
        ->and($countdownEvent['daysUntil'])->toBe(5);
});

it('returns null when no countdown event exists', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-23',
            'end' => '2026-02-23',
            'name' => '不倒數的活動',
            'countdown' => false,
        ],
    ]]);

    $countdownEvent = $this->service->getCountdownEvent('2026-02-18');

    expect($countdownEvent)->toBeNull();
});

it('returns ongoing countdown event', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-15',
            'end' => '2026-02-20',
            'name' => '進行中且需要倒數',
            'countdown' => true,
        ],
    ]]);

    $countdownEvent = $this->service->getCountdownEvent('2026-02-18');

    expect($countdownEvent)->not->toBeNull()
        ->and($countdownEvent['status'])->toBe('ongoing')
        ->and($countdownEvent['daysUntil'])->toBe(0);
});

it('returns first countdown event when multiple exist', function () {
    config(['app.current_semester' => '2025B']);
    config(['school-schedules.2025B' => [
        [
            'start' => '2026-02-25',
            'end' => '2026-02-25',
            'name' => '第二個倒數活動',
            'countdown' => true,
        ],
        [
            'start' => '2026-02-20',
            'end' => '2026-02-20',
            'name' => '第一個倒數活動',
            'countdown' => true,
        ],
    ]]);

    $countdownEvent = $this->service->getCountdownEvent('2026-02-18');

    expect($countdownEvent)->not->toBeNull()
        ->and($countdownEvent['name'])->toBe('第一個倒數活動');
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

    $events = $this->service->getUpcomingAndOngoingEvents('2026-02-18');

    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('多日活動')
        ->and($events[0]['start']->format('Y-m-d'))->toBe('2026-05-01')
        ->and($events[0]['end']->format('Y-m-d'))->toBe('2026-05-20')
        ->and($events[0]['daysUntil'])->toBe(72);
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

    $events = $this->service->getUpcomingAndOngoingEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]['status'])->toBe('upcoming');
});
