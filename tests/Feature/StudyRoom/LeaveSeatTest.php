<?php

use App\Events\StudyRoomUpdated;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Event;

function leaveSeatCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

it('clears occupancy when leaving a held seat', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', leaveSeatCookie($schedule))
        ->postJson(route('study-room.seat.leave'));

    $response->assertOk()->assertJsonPath('ok', true);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull()
        ->and($seat->occupied_at)->toBeNull()
        ->and($seat->last_seen_at)->toBeNull()
        ->and($seat->activity_verb)->toBeNull()
        ->and($seat->timer_mode)->toBeNull()
        ->and($seat->timer_phase)->toBeNull()
        ->and($seat->timer_started_at)->toBeNull()
        ->and($seat->timer_ends_at)->toBeNull();

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'seat.left' && $event->seat?->code === $seat->code
    );
});

it('is a no-op when the viewer holds no seat', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', leaveSeatCookie($schedule))
        ->postJson(route('study-room.seat.leave'));

    $response->assertOk()->assertJsonPath('ok', true);

    Event::assertNotDispatched(StudyRoomUpdated::class);
});

it('rejects leaving without a profile', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', leaveSeatCookie($schedule))
        ->postJson(route('study-room.seat.leave'));

    $response->assertStatus(403);
});
