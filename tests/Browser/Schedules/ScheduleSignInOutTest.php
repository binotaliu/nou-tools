<?php

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;

// The device cookie is HttpOnly and issued by the server, so only a real
// browser proves the whole loop: recover a schedule, stay signed in across
// page loads, sign out from 設定 and be an anonymous visitor again.

it('signs in from a backup link and signs out again from the settings page', function () {
    $schedule = StudentSchedule::factory()->create();

    $page = visit(route('schedules.my'));

    $page->click('[data-testid="find-schedule-existing"]')
        ->fill('[data-testid="find-schedule-url"]', $schedule->getRouteKey())
        ->click('[data-testid="find-schedule-submit"]');

    // An Inertia visit, so there is no page load to wait for.
    waitUntil($page, "location.pathname !== '/schedules/my'");

    expect(ScheduleDevice::query()->where('student_schedule_id', $schedule->id)->count())->toBe(1);

    $page->navigate('/settings')
        ->assertVisible('[data-testid="settings-schedule-sign-out"]')
        ->click('[data-testid="settings-schedule-sign-out"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="settings-schedule-no-schedule"]\') !== null');

    expect(ScheduleDevice::query()->count())->toBe(0);

    $page->navigate('/schedules/my')
        ->assertVisible('[data-testid="find-schedule-existing"]');
});

it('does not offer sign out to a visitor with no schedule', function () {
    visit('/settings')->assertMissing('[data-testid="settings-schedule-sign-out"]');
});

it('keeps a local copy of the device token after signing in and removes it on sign out', function () {
    $schedule = StudentSchedule::factory()->create();

    $page = visit(route('schedules.my'));

    $page->click('[data-testid="find-schedule-existing"]')
        ->fill('[data-testid="find-schedule-url"]', $schedule->getRouteKey())
        ->click('[data-testid="find-schedule-submit"]');

    waitUntil($page, "localStorage.getItem('schedule-device:v1') !== null");

    $saved = json_decode($page->script("localStorage.getItem('schedule-device:v1')"), true);
    expect(ScheduleDevice::query()->where('token_hash', ScheduleDevice::hashToken($saved['token']))->exists())->toBeTrue();

    $page->navigate('/settings')->click('[data-testid="settings-schedule-sign-out"]');

    waitUntil($page, "localStorage.getItem('schedule-device:v1') === null");
    waitUntil($page, 'document.querySelector(\'[data-testid="settings-schedule-no-schedule"]\') !== null');
});

it('signs a browser back in from its local copy when the cookie is gone', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('kept-in-storage')->create();

    $page = visit('/settings');
    $page->assertPresent('[data-testid="settings-schedule-no-schedule"]');

    $page->script("localStorage.setItem('schedule-device:v1', JSON.stringify({ token: 'kept-in-storage', fingerprint: 'stale' })); 0");
    $page->navigate('/settings');

    waitUntil($page, 'document.querySelector(\'[data-testid="settings-schedule-sign-out"]\') !== null');
});

it('discards a local copy the server no longer accepts', function () {
    $page = visit('/settings');
    $page->assertPresent('[data-testid="settings-schedule-no-schedule"]');

    $page->script("localStorage.setItem('schedule-device:v1', JSON.stringify({ token: 'revoked-elsewhere', fingerprint: 'stale' })); 0");
    $page->navigate('/settings');

    waitUntil($page, "localStorage.getItem('schedule-device:v1') === null");
    $page->assertPresent('[data-testid="settings-schedule-no-schedule"]');
});
