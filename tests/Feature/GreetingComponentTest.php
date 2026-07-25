<?php

use App\View\Components\Greeting;
use Illuminate\Support\Facades\Config;

it('exposes semester facts from config for the client to render', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $component = new Greeting;

    expect($component->semesterCode)->toBe('2025B')
        ->and($component->semesterLabel)->toBe('114 學年度下學期')
        ->and($component->semesterStart)->toBe('2026-02-23')
        ->and($component->semesterEnd)->toBe('2026-07-05');
});

it('leaves the semester range null when it is not configured', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', []);

    $component = new Greeting;

    expect($component->semesterStart)->toBeNull()
        ->and($component->semesterEnd)->toBeNull()
        ->and($component->semesterLabel)->toBe('114 學年度下學期');
});
