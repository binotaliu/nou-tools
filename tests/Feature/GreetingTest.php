<?php

use Illuminate\Support\Facades\Config;

it('shows semester week during the semester on the home page', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $this->travelTo('2026-02-24');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSeeInOrder([
            '今天是 2026 年 2 月 24 日',
            '(二)',
            '114 學年度下學期第一週',
        ]);
});

it('shows "尚未開始" when today is before semester start', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $this->travelTo('2026-02-22');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('114 學年度下學期尚未開始');
});

it('shows "已結束" when today is after semester end', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', ['2026-02-23', '2026-07-05']);

    $this->travelTo('2026-07-06');

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('114 學年度下學期已結束');
});
