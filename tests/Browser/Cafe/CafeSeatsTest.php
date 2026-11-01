<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Str;

// Seats are claimed through resources/js/Composables/useCafeSocket.js, so the
// flow is only observable in a real browser.

it('shows the café to a visitor with a sign-up hint and no way to sit', function () {
    $page = visit(route('cafe.show'));

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="cafe-scene"]')
        ->assertVisible('[data-testid="cafe-small-tables"]')
        ->assertVisible('[data-testid="cafe-needs-schedule"]');

    waitUntil($page, 'document.querySelectorAll(\'[data-seat-code]\').length === 20');

    $disabled = $page->script("document.querySelector('[data-testid=\"cafe-quick-seat\"]').disabled");

    expect($disabled)->toBeTrue();
});

it('lets a signed-in student sit down and get up', function () {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Browser Café Schedule']);
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '拿鐵同學']);

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    waitUntil($page, 'document.cookie !== undefined', 2000);

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelectorAll(\'[data-seat-code]\').length === 20');

    $page->click('[data-testid="cafe-seat-B01"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-leave"]\') !== null');

    $page->assertSeeIn('[data-testid="cafe-seat-B01"]', '拿鐵同學');

    expect(StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'B01')->value('student_schedule_id'))->toBe($schedule->id);

    $page->click('[data-testid="cafe-leave"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-quick-seat"]\') !== null');

    expect(StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'B01')->value('student_schedule_id'))->toBeNull();
});
