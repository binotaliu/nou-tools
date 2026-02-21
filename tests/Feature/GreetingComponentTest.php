<?php

use App\View\Components\Greeting;
use Illuminate\Support\Facades\Config;

it('computes semester info during the semester (component)', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $this->travelTo('2026-02-24');

    $component = new Greeting;

    expect($component->dateString)->toBe('2026 年 2 月 24 日 (二)')
        ->and($component->semesterInfo)->toBe('114 學年度下學期第一週');
});

it('shows 尚未開始 before semester (component)', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $this->travelTo('2026-02-22');

    $component = new Greeting;

    expect($component->semesterInfo)->toBe('114 學年度下學期尚未開始');
});

it('shows 已結束 after semester (component)', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $this->travelTo('2026-07-06');

    $component = new Greeting;

    expect($component->semesterInfo)->toBe('114 學年度下學期已結束');
});
