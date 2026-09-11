<?php

use App\Events\StudyRoomUpdated;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Event;

function studyRoomCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

function withStudyRoomProfile(StudentSchedule $schedule): StudentSchedule
{
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    return $schedule;
}

it('claims an empty seat', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = withStudyRoomProfile(StudentSchedule::factory()->create());
    $seat = StudyRoomSeat::factory()->create(['floor' => 1]);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomCookie($schedule))
        ->postJson(route('study-room.seats.take', $seat));

    $response->assertOk()->assertJsonPath('ok', true);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBe($schedule->id)
        ->and($seat->occupied_at)->not->toBeNull()
        ->and($seat->last_seen_at)->not->toBeNull();

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'seat.taken' && $event->seat?->code === $seat->code
    );
});

it('rejects a seat someone else already holds', function () {
    Event::fake([StudyRoomUpdated::class]);

    $occupant = withStudyRoomProfile(StudentSchedule::factory()->create());
    $seat = StudyRoomSeat::factory()->create(['floor' => 1]);
    $seat->update(['student_schedule_id' => $occupant->id, 'occupied_at' => now(), 'last_seen_at' => now()]);

    $challenger = withStudyRoomProfile(StudentSchedule::factory()->create());

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomCookie($challenger))
        ->postJson(route('study-room.seats.take', $seat));

    $response->assertStatus(409)->assertJsonPath('message', '這個位子已經有人坐了。');

    $seat->refresh();
    expect($seat->student_schedule_id)->toBe($occupant->id);
});

it('moves a student to a new seat and frees the old one, without a UNIQUE crash', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = withStudyRoomProfile(StudentSchedule::factory()->create());
    $firstSeat = StudyRoomSeat::factory()->create(['floor' => 1]);
    $secondSeat = StudyRoomSeat::factory()->create(['floor' => 1]);

    $this->withCredentials()
        ->withCookie('student_schedule', studyRoomCookie($schedule))
        ->postJson(route('study-room.seats.take', $firstSeat))
        ->assertOk();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomCookie($schedule))
        ->postJson(route('study-room.seats.take', $secondSeat));

    $response->assertOk();

    $firstSeat->refresh();
    $secondSeat->refresh();

    expect($firstSeat->student_schedule_id)->toBeNull()
        ->and($secondSeat->student_schedule_id)->toBe($schedule->id);

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'seat.taken' && $event->seat?->code === $secondSeat->code
    );
});

it('rejects claiming a seat on a floor that is not open', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = withStudyRoomProfile(StudentSchedule::factory()->create());
    $seat = StudyRoomSeat::factory()->create(['floor' => 3]);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomCookie($schedule))
        ->postJson(route('study-room.seats.take', $seat));

    $response->assertStatus(422);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull();
});

it('rejects taking a seat without a profile', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->create(['floor' => 1]);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomCookie($schedule))
        ->postJson(route('study-room.seats.take', $seat));

    $response->assertStatus(403);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull();
});

it('rejects taking a seat with no schedule cookie', function () {
    $seat = StudyRoomSeat::factory()->create(['floor' => 1]);

    $response = $this->withCredentials()->postJson(route('study-room.seats.take', $seat));

    $response->assertStatus(403);
});
