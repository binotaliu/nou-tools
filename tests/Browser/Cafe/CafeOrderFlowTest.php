<?php

use App\Models\AcornTransaction;
use App\Models\CafeOrder;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Str;

// Talking to the barista, ordering and the cup that shows up on both seats are
// driven client-side (useBarista.js, MenuSheet.vue, useCafeSocket.js).

$signedInStudent = function ($test): array {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Browser Order Schedule']);
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '點餐同學']);
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(10)->create();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    waitUntil($page, 'document.cookie !== undefined', 2000);

    return [$schedule, $page];
};

it('greets with a line, takes an order, spends acorns and puts the cup on the seat after a moment', function () use ($signedInStudent) {
    config()->set('cafe.coffee.delivery_seconds', 2);
    [$schedule, $page] = $signedInStudent($this);

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelectorAll(\'[data-seat-code]\').length === 20');
    $page->assertMissing('[data-testid="cafe-barista"]')
        ->click('[data-testid="cafe-talk-barista"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-barista-line"]\')?.textContent.trim().length > 0');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-acorns"]\') !== null');

    $page->assertSeeIn('[data-testid="cafe-acorns"]', '10 顆橡實')
        ->click('[data-testid="cafe-seat-B01"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-leave"]\') !== null');
    $page->assertVisible('[data-testid="study-room-control-panel"]')
        ->assertVisible('[data-testid="cafe-coffee-empty"]');

    $page->click('[data-testid="cafe-open-menu"]');

    waitUntil($page, 'document.querySelectorAll(\'[data-testid^="cafe-order-"]\').length === 5');

    $page->assertSeeIn('[data-testid="cafe-menu-balance"]', '10');

    $page->click('[data-testid="cafe-order-2"]');

    // Being made first, then on the table.
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-coffee-making"]\') !== null');
    expect($page->script('document.querySelector(\'[data-testid="cafe-seat-B01-coffee"]\') === null'))->toBeTrue();

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-seat-B01-coffee"]\') !== null', 8000);

    $page->assertMissing('[data-testid="cafe-menu"]')
        ->assertSeeIn('[data-testid="cafe-acorns"]', '7 顆橡實');

    expect(CafeOrder::query()->where('student_schedule_id', $schedule->id)->count())->toBe(1);

    // Sip, then leave: the dine-in cup is thrown away.
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-coffee-sip"]\')?.disabled === false');
    $page->click('[data-testid="cafe-coffee-sip"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-coffee-level"]\')?.textContent.includes("4 / 5")');

    expect(CafeOrder::query()->where('student_schedule_id', $schedule->id)->value('sips_taken'))->toBe(1);

    $page->click('[data-testid="cafe-leave"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-quick-seat"]\') !== null');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-seat-B01-coffee"]\') === null');

    expect($schedule->fresh()->activeCafeOrder)->toBeNull();
});

it('runs the focus timer from the panel on a café seat and keeps it when leaving the page', function () use ($signedInStudent) {
    [$schedule, $page] = $signedInStudent($this);

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelectorAll(\'[data-seat-code]\').length === 20');
    $page->click('[data-testid="cafe-seat-B02"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-start-timer"]\') !== null');

    // No drink, no focus: the server says so in the panel.
    $page->click('[data-testid="study-room-start-timer"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-timer-error"]\') !== null');
    $page->assertSeeIn('[data-testid="study-room-timer-error"]', '先點一杯飲料');

    CafeOrder::factory()->for($schedule, 'schedule')->create();
    $page->click('[data-testid="study-room-start-timer"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-timer-panel"]\') !== null');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-seat-B02-focusing"]\') !== null');

    $seat = StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'B02')->firstOrFail();
    expect($seat->timer_phase?->value)->toBe('focus');

    // Away from the page the seat stays, and the shell pill leads back.
    $page->click('[data-testid="study-room-banner-minimize"]')
        ->click('[data-testid="site-footer"] a[href="/about"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="shell-player-room"]\') !== null');

    expect($page->script('document.querySelector(\'[data-testid="shell-player-room"]\').getAttribute("href")'))->toBe('/cafe')
        ->and($seat->fresh()->student_schedule_id)->toBe($schedule->id);
});

it('shows the cup on the study room carrel too', function () use ($signedInStudent) {
    [$schedule, $page] = $signedInStudent($this);

    StudyRoomSeat::factory()->occupiedBy($schedule)->create([
        'floor' => 1,
        'seat_number' => 1,
        'code' => '1-S01',
        'label' => '1F 單人座 01',
    ]);
    CafeOrder::factory()->for($schedule, 'schedule')->create(['name' => '拿鐵', 'emoji' => '🥛']);

    $page->navigate(route('study-room.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="seat-1-S01-coffee"]\') !== null');

    $page->assertVisible('[data-testid="seat-1-S01-coffee"]');

    $label = $page->script("document.querySelector('[data-testid=\"seat-1-S01\"]').getAttribute('aria-label')");

    expect($label)->toContain('桌上有一杯拿鐵');
});
