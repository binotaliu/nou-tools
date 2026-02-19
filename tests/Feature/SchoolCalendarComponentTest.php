<?php

use App\View\Components\SchoolCalendar;
use Illuminate\Support\Facades\Config;

it('loads events from SchoolScheduleService when no props supplied (component)', function () {
    Config::set('app.current_semester', '2025B');

    // before the 2026-02-23 countdown event
    $this->travelTo('2026-02-20');

    $component = new SchoolCalendar;

    expect($component->scheduleEvents)->not->toBeEmpty()
        ->and($component->countdownEvent)->not->toBeNull()
        ->and($component->countdownEvent['name'])->toBe('114下學期課程開播')
        ->and($component->countdownEvent['status'])->toBe('upcoming');
});

it('allows overriding scheduleEvents and countdownEvent via constructor (component)', function () {
    $overrideEvents = [
        [
            'name' => 'Custom Event',
            'start' => \Carbon\Carbon::parse('2026-05-01', 'Asia/Taipei'),
            'end' => \Carbon\Carbon::parse('2026-05-01', 'Asia/Taipei'),
            'status' => 'upcoming',
            'daysUntil' => 12,
            'countdown' => false,
        ],
    ];

    $component = new SchoolCalendar($overrideEvents, []);

    expect($component->scheduleEvents[0]['name'])->toBe('Custom Event')
        ->and($component->countdownEvent)->toBe([]);
});
