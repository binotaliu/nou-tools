<?php

use App\Models\AcornTransaction;
use App\Models\ArcadePlay;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Illuminate\Support\Str;

// The cabinet on the floor, the kiosk modal and the horoscope game are driven
// client-side (CafeFloor.vue, ArcadeMachine.vue, HoroscopeGame.vue).

$signedInPlayer = function (int $acorns): array {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Browser Arcade Schedule']);
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '玩家同學']);
    AcornTransaction::factory()->for($schedule, 'schedule')->credit($acorns)->create();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    waitUntil($page, 'document.cookie !== undefined', 2000);

    return [$schedule, $page];
};

it('opens the cabinet from the floor, spends a coin on a sign and shows the reading', function () use ($signedInPlayer) {
    [$schedule, $page] = $signedInPlayer(10);

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-arcade-open"]\') !== null');
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-acorns"]\') !== null');

    $page->click('[data-testid="cafe-arcade-open"]')
        ->assertVisible('[data-testid="cafe-arcade-modal"]')
        ->click('[data-testid="arcade-game-horoscope"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-sign-leo"]\') !== null');

    $page->click('[data-testid="horoscope-sign-leo"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-insert-coin"]\')?.disabled === false');
    $page->click('[data-testid="horoscope-insert-coin"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-result"]\') !== null', 8000);

    $page->assertSeeIn('[data-testid="horoscope-result-heading"]', '獅子座')
        ->assertSeeIn('[data-testid="horoscope-score-overall"]', '整體運')
        ->assertSeeIn('[data-testid="cafe-acorns"]', '8 顆橡實');

    expect(ArcadePlay::query()->where('student_schedule_id', $schedule->id)->value('choice'))->toBe('leo');

    // The sign is already unlocked: asking again is free.
    $page->click('[data-testid="horoscope-again"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-sign-leo"]\') !== null');

    $page->assertSee('今日已解鎖');
    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-insert-coin"]\')?.disabled === false');
    $page->click('[data-testid="horoscope-insert-coin"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-result"]\') !== null');

    $page->assertSeeIn('[data-testid="cafe-acorns"]', '8 顆橡實');

    expect(ArcadePlay::query()->count())->toBe(1);
});

it('keeps the coin slot shut for a visitor without a schedule', function () {
    $page = visit(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-arcade-open"]\') !== null');

    $page->click('[data-testid="cafe-arcade-open"]')
        ->click('[data-testid="arcade-game-horoscope"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-sign-aries"]\') !== null');

    $page->click('[data-testid="horoscope-sign-aries"]');
    waitUntil($page, 'document.querySelector(\'[data-testid="horoscope-hint"]\') !== null');

    expect($page->script('document.querySelector(\'[data-testid="horoscope-insert-coin"]\').disabled'))->toBeTrue();
});
