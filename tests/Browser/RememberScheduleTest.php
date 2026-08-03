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

    $page = visit(route('schedules.show', $schedule));

    // See the comment in the "already remembered" test below for why this
    // wait matters before a second navigation to the same schedule URL.
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
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

    $page = visit(route('schedules.show', $schedule));

    // Wait for the offline-support service worker (public/sw.js) to finish
    // installing/activating on this first load before triggering the
    // form-submit redirect below. Otherwise the worker's activation can land
    // mid-navigation on the redirected page and abort the following reload
    // (net::ERR_ABORTED; maybe frame was detached) — see
    // Tests\Browser\OfflineServiceWorkerTest, which waits the same way.
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    $page->navigate(route('schedules.show', $schedule))
        ->assertMissing('[data-testid="remember-schedule-modal"]');
});
