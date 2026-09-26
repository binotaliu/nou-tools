<?php

it('announces the scanner, offers a non-camera alternative and closes on Escape', function () {
    $page = visit(route('schedules.my'));

    $page->click('[data-testid="find-schedule-existing"]');

    if ($page->script("typeof navigator.mediaDevices?.getUserMedia === 'function'") !== true) {
        $this->markTestSkipped('No getUserMedia in this browser.');
    }

    $page->click('[data-testid="find-schedule-scan"]')
        ->assertVisible('[data-testid="find-schedule-scanner-modal"]')
        ->assertAttribute('[role="dialog"]', 'aria-modal', 'true')
        ->assertPresent('[data-testid="qr-scanner-status"][role="status"][aria-live="polite"]')
        ->assertSee('不方便使用相機')
        ->assertVisible('[data-testid="qr-scanner-close"]')
        ->keys('[data-testid="qr-scanner-close"]', 'Escape')
        ->assertMissing('[data-testid="find-schedule-scanner-modal"]');
});

it('labels the camera preview', function () {
    $source = file_get_contents(resource_path('js/Components/QrScanner.vue'));

    expect($source)
        ->toContain('<video')
        ->toContain('aria-label="相機預覽畫面')
        ->toContain('role="status"')
        ->toContain('aria-live="polite"');
});
