<?php

use App\Models\CalendarDay;
use App\Models\SchoolCalendarEvent;

// Past / ongoing / upcoming are decided in the browser from the viewer's
// Taipei calendar date, so they are only observable in a real browser.

it('marks events as ended, ongoing or upcoming and switches semesters', function () {
    config(['app.current_semester' => '2026A']);

    $today = now('Asia/Taipei');

    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->subDays(10)->toDateString(), $today->copy()->subDays(9)->toDateString())
        ->create(['name' => '瀏覽器已結束活動']);
    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->subDay()->toDateString(), $today->copy()->addDay()->toDateString())
        ->create(['name' => '瀏覽器進行中活動']);
    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->addDays(20)->toDateString(), $today->copy()->addDays(20)->toDateString())
        ->minor()
        ->create(['name' => '瀏覽器未來次要活動']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '瀏覽器另一學期活動']);

    visit(route('school-calendar.index'))
        ->withTimezone('Asia/Taipei')
        ->assertNoJavaScriptErrors()
        ->click('[data-testid="school-calendar-expand-all"]')
        ->assertSee('瀏覽器已結束活動')
        ->assertSee('已結束')
        ->assertSee('瀏覽器進行中活動')
        ->assertSee('進行中')
        ->assertSee('瀏覽器未來次要活動')
        ->assertDontSee('瀏覽器另一學期活動')
        ->select('[data-testid="school-calendar-term"]', '2025B')
        ->assertSee('瀏覽器另一學期活動')
        ->assertDontSee('瀏覽器進行中活動');
});

it('shows the month calendar by default and remembers a switch to the list', function () {
    config(['app.current_semester' => '2026A']);

    $today = now('Asia/Taipei');

    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->subDay()->toDateString(), $today->copy()->addDay()->toDateString())
        ->create(['name' => '月曆檢視活動']);

    visit(route('school-calendar.index'))
        ->withTimezone('Asia/Taipei')
        ->assertNoJavaScriptErrors()
        ->assertPresent('[data-testid="school-calendar-month"]')
        ->assertSee('月曆檢視活動')
        ->assertPresent('[data-testid="calendar-today"]')
        ->click('[data-testid="school-calendar-view-list"]')
        ->assertMissing('[data-testid="school-calendar-month"]')
        ->refresh()
        ->assertMissing('[data-testid="school-calendar-month"]')
        ->click('[data-testid="school-calendar-view-calendar"]')
        ->assertPresent('[data-testid="school-calendar-month"]');
});

it('paints marked dates red with their note and lets a weekend be plain', function () {
    config(['app.current_semester' => '2026A']);

    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between('2026-10-05', '2026-10-25')
        ->create(['name' => '標註月曆活動']);
    // 2026-10-10 is a Saturday that is a holiday; the 17th is a Saturday made a workday; the 14th is a plain Wednesday.
    CalendarDay::factory()->on('2026-10-10')->labelled('國慶日')->create();
    CalendarDay::factory()->on('2026-10-17')->plain()->labelled('補班')->create();
    CalendarDay::factory()->on('2026-10-14')->labelled('校慶')->create();

    visit(route('school-calendar.index', ['term' => '2026A']))
        ->withTimezone('Asia/Taipei')
        ->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="calendar-day-note-2026-10-10"]', '國慶日')
        ->assertSeeIn('[data-testid="calendar-day-note-2026-10-17"]', '補班')
        ->assertAttribute('[data-testid="calendar-day-label-2026-10-10"]', 'data-red', 'true')
        ->assertAttribute('[data-testid="calendar-day-label-2026-10-14"]', 'data-red', 'true')
        ->assertAttribute('[data-testid="calendar-day-label-2026-10-17"]', 'data-red', 'false')
        ->assertAttribute('[data-testid="calendar-day-label-2026-10-11"]', 'data-red', 'true')
        ->assertAttribute('[data-testid="calendar-day-label-2026-10-13"]', 'data-red', 'false');
});

it('shows one month at a time, steps between months and can expand them all', function () {
    config(['app.current_semester' => '2026A']);

    $today = now('Asia/Taipei')->startOfMonth();

    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->subMonth()->toDateString(), $today->copy()->subMonth()->toDateString())
        ->create(['name' => '上月單月活動']);
    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->addDay()->toDateString(), $today->copy()->addDay()->toDateString())
        ->create(['name' => '本月單月活動']);
    SchoolCalendarEvent::factory()->forTerm('2026A')
        ->between($today->copy()->addMonth()->toDateString(), $today->copy()->addMonth()->toDateString())
        ->create(['name' => '下月單月活動']);

    visit(route('school-calendar.index', ['term' => '2026A']))
        ->withTimezone('Asia/Taipei')
        ->assertNoJavaScriptErrors()
        ->click('[data-testid="school-calendar-view-list"]')
        ->assertSee('本月單月活動')
        ->assertDontSee('上月單月活動')
        ->assertDontSee('下月單月活動')
        ->click('[data-testid="school-calendar-next"]')
        ->assertSee('下月單月活動')
        ->assertDontSee('本月單月活動')
        ->assertPresent('[data-testid="school-calendar-current-month"]')
        ->click('[data-testid="school-calendar-current-month"]')
        ->assertSee('本月單月活動')
        ->click('[data-testid="school-calendar-previous"]')
        ->assertSee('上月單月活動')
        ->click('[data-testid="school-calendar-expand-all"]')
        ->assertSee('上月單月活動')
        ->assertSee('本月單月活動')
        ->assertSee('下月單月活動');
});
