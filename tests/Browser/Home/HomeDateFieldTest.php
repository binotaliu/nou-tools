<?php

use Illuminate\Support\Carbon;

// The 今日視訊面授 date filter is a custom picker (native date inputs render
// differently per browser). Picking a day has to navigate with ?date=, which
// only a real browser can confirm.

it('shows the selected date in full and navigates when a day is picked', function () {
    $page = visit(route('home', ['date' => '2026-03-05']));

    $page->assertSeeIn('#video-course-date', '2026/3/5（四）')
        ->assertMissing('input[type="date"]')
        ->click('#video-course-date')
        ->assertSeeIn('[data-testid="date-field-popover"]', '2026 年 3 月')
        ->assertMissing('[data-testid="date-field-clear"]')
        ->click('[data-testid="date-field-popover"] [data-date="2026-03-12"]')
        ->assertQueryStringHas('date', '2026-03-12')
        ->assertSeeIn('#video-course-date', '2026/3/12（四）');
});

it('jumps back to today from the calendar', function () {
    $today = Carbon::now('Asia/Taipei');

    $page = visit(route('home', ['date' => '2026-03-05']));

    $page->click('#video-course-date')
        ->click('[data-testid="date-field-today"]')
        ->assertQueryStringHas('date', $today->format('Y-m-d'));
});
