<?php

use Illuminate\Support\Carbon;

// Countdown days, status ("進行中"), date formatting, and the "Taiwan time"
// hint are computed in the browser from the viewer's own clock, anchored to
// the Asia/Taipei calendar date rather than the viewer's local date (see
// window.nouToolsSchoolCalendar in resources/js/app.js). These behaviours are
// only observable with a real browser, not the Feature tests that merely
// assert the raw event payload is embedded.

it('shows the countdown anchored to Asia/Taipei without a hint for a viewer in Asia/Taipei', function () {
    config(['app.current_semester' => '2025B']);

    $start = now('Asia/Taipei')->addDays(5)->toDateString();

    config(['school-schedules.2025B' => [
        [
            'start' => $start,
            'end' => $start,
            'name' => '瀏覽器倒數活動',
            'countdown' => true,
        ],
    ]]);

    visit('/')
        ->withTimezone('Asia/Taipei')
        ->assertSee('瀏覽器倒數活動')
        ->assertSee('5')
        ->assertSee('天後')
        ->assertDontSee('此區塊日期皆為台灣時間')
        ->screenshot();
});

it('shows the same Taipei-anchored countdown plus a "Taiwan time" hint for a viewer whose timezone differs', function () {
    config(['app.current_semester' => '2025B']);

    $start = now('Asia/Taipei')->addDays(5)->toDateString();

    config(['school-schedules.2025B' => [
        [
            'start' => $start,
            'end' => $start,
            'name' => '瀏覽器倒數活動',
            'countdown' => true,
        ],
    ]]);

    visit('/')
        ->withTimezone('America/Los_Angeles')
        ->assertSee('瀏覽器倒數活動')
        ->assertSee('5')
        ->assertSee('天後')
        ->assertSee('此區塊日期皆為台灣時間')
        ->screenshot();
});

it('shows 進行中 for an event that is ongoing on the Taipei calendar', function () {
    config(['app.current_semester' => '2025B']);

    $start = now('Asia/Taipei')->subDays(2)->toDateString();
    $end = now('Asia/Taipei')->addDays(2)->toDateString();

    config(['school-schedules.2025B' => [
        [
            'start' => $start,
            'end' => $end,
            'name' => '瀏覽器進行中活動',
            'countdown' => true,
        ],
    ]]);

    visit('/')
        ->withTimezone('Asia/Taipei')
        ->assertSee('瀏覽器進行中活動')
        ->assertSee('進行中')
        ->assertDontSee('此區塊日期皆為台灣時間')
        ->screenshot();
});

it('formats a multi-day event date range in Chinese', function () {
    config(['app.current_semester' => '2025B']);

    $start = Carbon::now('Asia/Taipei')->addDays(10);
    $end = Carbon::now('Asia/Taipei')->addDays(15);

    config(['school-schedules.2025B' => [
        [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'name' => '瀏覽器選課期間',
            'countdown' => false,
        ],
    ]]);

    visit('/')
        ->withTimezone('Asia/Taipei')
        ->assertSee('瀏覽器選課期間')
        ->assertSee($start->format('n').' 月 '.$start->format('j').' 日')
        ->assertSee($end->format('n').' 月 '.$end->format('j').' 日')
        ->screenshot();
});
