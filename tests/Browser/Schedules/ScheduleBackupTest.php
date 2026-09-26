<?php

use App\Models\StudentSchedule;

// The dialog, the PWA-only entry points and the one-time nudge are all
// client-side, so they are checked in a real browser.
const BACKUP_PHONE = [390, 844];

$dismissRememberModal = function ($page): void {
    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'[data-testid="schedule-backup-open"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }
};

it('opens the backup dialog with a QR code and does not overflow a phone', function () use ($dismissRememberModal) {
    $schedule = StudentSchedule::factory()->create(['name' => '我的備份課表']);

    $page = visit(route('schedules.show', $schedule, absolute: false))->resize(...BACKUP_PHONE);
    $dismissRememberModal($page);

    $page->assertVisible('[data-testid="schedule-backup-card-entry"]')
        ->assertMissing('[data-testid="schedule-backup-dialog"]')
        ->click('[data-testid="schedule-backup-open"]')
        ->assertVisible('[data-testid="schedule-backup-dialog"]')
        ->assertVisible('[data-testid="schedule-backup-qr"] svg')
        ->assertSee('我的備份課表')
        ->screenshot(filename: 'schedule-backup-dialog');

    expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();

    $page->click('[data-testid="schedule-backup-close"]')
        ->assertMissing('[data-testid="schedule-backup-dialog"]');
});

it('moves the backup into the 更多 menu in an installed PWA', function () use ($dismissRememberModal) {
    $schedule = StudentSchedule::factory()->create();

    $page = visit(route('schedules.show', $schedule, absolute: false))->resize(...BACKUP_PHONE);
    $dismissRememberModal($page);

    $page->script("document.documentElement.dataset.pwa = ''");

    $page->assertMissing('[data-testid="schedule-backup-card-entry"]')
        ->click('[data-testid="schedule-actions-toggle"]')
        ->click('[data-testid="schedule-actions-backup"]')
        ->assertVisible('[data-testid="schedule-backup-dialog"]')
        ->assertMissing('[data-testid="schedule-actions-menu"]');
});
