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
