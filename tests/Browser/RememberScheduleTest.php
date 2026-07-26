<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

// The modal's visibility also depends on the browser's live network status
// (Alpine.store('network').offline), so it's only reliably observable with a
// real browser rather than the server-rendered Feature test.

it('prompts to remember the schedule and hides the modal after confirming', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Remember Schedule',
    ]);

    visit(route('schedules.show', $schedule))
        ->assertVisible('[data-testid="remember-schedule-modal"]')
        ->assertSee('要記住這個課表嗎？')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]')
        ->screenshot();
});

it('dismisses the remember-schedule modal without saving a cookie when declined', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Dismiss Schedule',
    ]);

    $page = visit(route('schedules.show', $schedule))
        ->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-dismiss"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    // Declining only hides the modal client-side; the server still has no
    // cookie, so reloading the page prompts again.
    $page->navigate(route('schedules.show', $schedule))
        ->assertVisible('[data-testid="remember-schedule-modal"]')
        ->screenshot();
});

it('does not prompt again once the schedule has already been remembered', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Already Remembered',
    ]);

    $page = visit(route('schedules.show', $schedule))
        ->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->waitForEvent('load')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    $page->navigate(route('schedules.show', $schedule))
        ->assertMissing('[data-testid="remember-schedule-modal"]');
});
