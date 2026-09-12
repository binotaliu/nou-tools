<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Settings\StudyRoomSettings;

function studyRoomScheduleCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

it('shows the schedule prompt and no seat-map root when there is no cookie', function () {
    $response = $this->get(route('study-room.show'));

    $response->assertOk()
        ->assertSee('先建立課表才能進自習室')
        ->assertSee(route('schedules.create'), false)
        ->assertDontSee('data-testid="study-room-root"', false);
});

it('shows the nickname form when there is a cookie but no profile', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    // The nickname/emoji form is now a PersonalInfo modal that's always
    // present in the DOM (hidden via x-show, forced open client-side by
    // Alpine when needsProfile is true) rather than conditionally
    // rendered server-side, so only its presence is asserted here —
    // whether it's actually open is a client-side concern covered by
    // tests/Browser/StudyRoomTest.php.
    $response->assertOk()
        ->assertSee('data-testid="study-room-profile-form"', false)
        ->assertSee('data-testid="study-room-root"', false);
});

it('shows the seat-map root when there is a cookie and a profile', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    $response->assertOk()->assertSee('data-testid="study-room-root"', false);
});

it('renders the announcement markdown as html', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $settings = app(StudyRoomSettings::class);
    $settings->announcement = '**重要公告**';
    $settings->save();
    app()->forgetInstance(StudyRoomSettings::class);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    $response->assertOk()->assertSee('<strong>', false);
});

it('shows the configured open hours label', function () {
    $response = $this->get(route('study-room.show'));

    $response->assertOk()->assertSee('24 小時');
});

it('auto-syncs seats from zero on first visit', function () {
    expect(StudyRoomSeat::query()->count())->toBe(0);

    $this->get(route('study-room.show'))->assertOk();

    $expectedSeatsPerFloor = config('study-room.layout.solo_seats_per_floor')
        + config('study-room.layout.tables_per_floor') * config('study-room.layout.seats_per_table');

    expect($expectedSeatsPerFloor)->toBe(24);
    expect(StudyRoomSeat::query()->count())->toBe(24 * config('study-room.floors.max'));
});

it('passes the maximum floor count to the client so the stairs can explain when the next floor opens', function () {
    config(['study-room.floors.max' => 5]);

    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomScheduleCookie($schedule))
        ->get(route('study-room.show'));

    $response->assertOk()
        ->assertSee('data-testid="study-room-stairs"', false)
        ->assertSee('&quot;maxFloors&quot;:5', false);
});
