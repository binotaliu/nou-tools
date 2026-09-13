<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

beforeEach(function () {
    app(SyncStudyRoomSeats::class)();
});

it('returns the study room state as JSON with the expected shape', function () {
    $response = $this->getJson(route('study-room.state'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'floors' => [
                '*' => ['floor', 'label', 'soloSeats', 'tables', 'occupiedCount', 'totalCount'],
            ],
            'openFloors',
            'totals' => ['occupantCount', 'siteFocusSecondsToday', 'yourFocusSecondsToday'],
            'serverTime',
            'version',
        ]);

    expect($response->json('openFloors'))->toBe(1);
});

it('returns 304 Not Modified on a repeat request with a matching ETag', function () {
    $first = $this->getJson(route('study-room.state'));
    $etag = $first->headers->get('ETag');

    expect($etag)->not->toBeNull();

    $second = $this->withHeaders(['If-None-Match' => $etag])->getJson(route('study-room.state'));

    $second->assertStatus(304);
});

it('never exposes student_schedule_id, the schedule uuid, or its route token', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => 'Tester']);

    $seat = StudyRoomSeat::query()->where('floor', 1)->where('kind', 'solo')->where('seat_number', 1)->first();
    $seat->update([
        'student_schedule_id' => $schedule->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);

    $response = $this->getJson(route('study-room.state'));
    $body = $response->getContent();

    expect($body)->not->toContain('student_schedule_id');
    expect($body)->not->toContain((string) $schedule->uuid);
    expect($body)->not->toContain((string) $schedule->getRouteKey());
});

it('marks isYou true only for the seat matching the viewer cookie', function () {
    $schedule = StudentSchedule::factory()->create();
    $other = StudentSchedule::factory()->create();

    $seat = StudyRoomSeat::query()->where('floor', 1)->where('kind', 'solo')->where('seat_number', 1)->first();
    $seat->update([
        'student_schedule_id' => $schedule->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);

    $otherSeat = StudyRoomSeat::query()->where('floor', 1)->where('kind', 'solo')->where('seat_number', 2)->first();
    $otherSeat->update([
        'student_schedule_id' => $other->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);

    $response = $this->withCredentials()->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->getJson(route('study-room.state'));

    $soloSeats = $response->json('floors.0.soloSeats');

    $yourSeat = collect($soloSeats)->firstWhere('code', $seat->code);
    $otherSoloSeat = collect($soloSeats)->firstWhere('code', $otherSeat->code);

    expect($yourSeat['isYou'])->toBeTrue();
    expect($otherSoloSeat['isYou'])->toBeFalse();
});

it('changes the version for two seat changes landing in the same second', function () {
    $this->travelTo(Date::parse('2026-09-11 10:00:00'));

    $buildState = app(BuildStudyRoomState::class);

    $first = $buildState(null);

    // Both writes land inside the same wall-clock second, so a version
    // derived from updated_at would be identical and the client would 304
    // past the second change forever.
    StudyRoomSeat::query()->where('code', '1-S01')
        ->update(['student_schedule_id' => StudentSchedule::factory()->create()->id]);
    $second = $buildState(null);

    StudyRoomSeat::query()->where('code', '1-S02')
        ->update(['student_schedule_id' => StudentSchedule::factory()->create()->id]);
    $third = $buildState(null);

    expect($second->version)->not->toBe($first->version)
        ->and($third->version)->not->toBe($second->version);
});

it('changes the version when the viewer completes a session even with no seat change', function () {
    $this->travelTo(Date::parse('2026-09-11 10:00:00'));

    $schedule = StudentSchedule::factory()->create();
    $viewer = StudentScheduleCookie::fromModel($schedule);

    $buildState = app(BuildStudyRoomState::class);

    $first = $buildState($viewer);

    // A session completing changes `yourFocusSecondsToday` without
    // touching any seat field, which is exactly the case the seat-only
    // signature used to miss.
    StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'started_at' => Date::now()->subMinutes(25),
        'ended_at' => Date::now(),
        'focus_seconds' => 25 * 60,
    ]);

    $second = $buildState($viewer);

    expect($second->version)->not->toBe($first->version);
});

it('tells the browser not to cache room state, so its own conditional requests never get a stale body', function () {
    $response = $this->getJson(route('study-room.state'));

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});
