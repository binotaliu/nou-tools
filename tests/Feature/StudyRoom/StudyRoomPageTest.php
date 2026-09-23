<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Settings\StudyRoomSettings;
use Inertia\Testing\AssertableInertia as Assert;

$studyRoomScheduleCookie = function (StudentSchedule $schedule): string {
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
};

it('shows the schedule prompt state when there is no cookie', function () {
    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('hasSchedule', false)
            ->where('needsProfile', false)
    );
});

it('flags that a profile is still needed when there is a cookie but no profile', function () use ($studyRoomScheduleCookie) {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    // The live seat map / profile check itself is a client-side concern
    // (the Vue page fetches /study-room/state and reacts to needsProfile),
    // covered by tests/Browser/StudyRoomTest.php — this only asserts the
    // page-shell prop the client bootstraps from.
    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('hasSchedule', true)
            ->where('needsProfile', true)
    );
});

it('does not flag needsProfile when there is a cookie and a profile', function () use ($studyRoomScheduleCookie) {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('hasSchedule', true)
            ->where('needsProfile', false)
    );
});

it('renders the announcement markdown as html', function () use ($studyRoomScheduleCookie) {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $settings = app(StudyRoomSettings::class);
    $settings->announcement = '**重要公告**';
    $settings->save();
    app()->forgetInstance(StudyRoomSettings::class);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('announcementHtml', fn (string $html): bool => str_contains($html, '<strong>'))
    );
});

it('shows the configured open hours label', function () {
    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('openHoursLabel', '24 小時')
    );
});

it('auto-syncs seats from zero on first visit', function () {
    expect(StudyRoomSeat::query()->count())->toBe(0);

    $this->get(route('study-room.show'))->assertOk();

    $expectedSeatsPerFloor = config('study-room.layout.solo_seats_per_floor')
        + config('study-room.layout.tables_per_floor') * config('study-room.layout.seats_per_table');

    expect($expectedSeatsPerFloor)->toBe(24);
    expect(StudyRoomSeat::query()->count())->toBe(24 * config('study-room.floors.max'));
});

it('passes the VAPID public key so the page can subscribe to timer notifications', function () {
    config(['webpush.vapid.public_key' => 'test-vapid-public-key']);

    $this->get(route('study-room.show'))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('vapidPublicKey', 'test-vapid-public-key')
    );
});

it('defaults the timer notification opt-in to off for a visitor with no profile', function () {
    $this->get(route('study-room.show'))->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('profile.notifyOnTimerEnd', false)
    );
});

it('passes the maximum floor count to the client so the stairs can explain when the next floor opens', function () {
    config(['study-room.floors.max' => 5]);

    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('clientConfig.maxFloors', 5)
    );
});

it('passes the seat layout counts to the client so the initial skeleton matches the real floor grid', function () {
    config(['study-room.layout' => [
        'solo_seats_per_floor' => 12,
        'tables_per_floor' => 3,
        'seats_per_table' => 4,
    ]]);

    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('clientConfig.soloSeatsPerFloor', 12)
            ->where('clientConfig.tablesPerFloor', 3)
            ->where('clientConfig.seatsPerTable', 4)
    );
});

it('passes the campus coordinates to the client so the windows can follow the real sun and moon', function () {
    config(['study-room.location' => ['latitude' => 25.0847, 'longitude' => 121.4737]]);

    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertInertia(
        fn (Assert $page) => $page->component('StudyRoom/Show')
            ->where('clientConfig.latitude', 25.0847)
            ->where('clientConfig.longitude', 121.4737)
    );
});

it('does not pass live room state as an Inertia prop', function () {
    // Load-bearing: the seat grid/timer state changes far faster than an
    // Inertia page-prop model should carry — see AGENTS.md "自習室 (Study
    // Room)" and .github/skills/laravel-best-practices/rules/inertia-vue-views.md.
    // The Vue page fetches it itself from GET /study-room/state instead.
    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertInertia(function (Assert $page) {
        $props = $page->toArray()['props'];

        expect($props)->not->toHaveKey('roomState');
    });
});
