<?php

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
