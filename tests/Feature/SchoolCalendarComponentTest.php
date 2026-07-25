<?php

use App\View\Components\SchoolCalendar;
use Illuminate\Support\Facades\Config;

it('loads events from ListUpcomingSchoolEvents when no props supplied (component)', function () {
    Config::set('app.current_semester', '2025B');

    // before the 2026-02-23 countdown event
    $this->travelTo('2026-02-20');

    $component = new SchoolCalendar;

    expect($component->events)->not->toBeEmpty()
        ->and(collect($component->events)->firstWhere('name', '114下學期課程開播'))
        ->not->toBeNull();
});

it('allows overriding events via constructor (component)', function () {
    $overrideEvents = [
        [
            'name' => 'Custom Event',
            'start' => '2026-05-01',
            'end' => '2026-05-01',
            'countdown' => false,
        ],
    ];

    $component = new SchoolCalendar($overrideEvents);

    expect($component->events)->toBe($overrideEvents);
});
