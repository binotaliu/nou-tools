<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
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
