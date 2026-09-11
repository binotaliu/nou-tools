<?php

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerPhase;
use App\Events\StudyRoomUpdated;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Event;

function heartbeatCookie(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

it('bumps last_seen_at for the caller\'s held seat', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create(['last_seen_at' => now()->subMinutes(2)]);

    $this->travel(1)->minute();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', heartbeatCookie($schedule))
        ->postJson(route('study-room.heartbeat'));

    $response->assertOk()->assertJsonPath('stillSeated', true);

    $seat->refresh();
    expect($seat->last_seen_at->diffInSeconds(now()))->toBeLessThan(2);
});

it('finalizes an expired focus timer on heartbeat', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Focus,
        'timer_started_at' => now(),
        'timer_ends_at' => now()->addMinutes(25),
    ]);

    $this->travel(26)->minutes();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', heartbeatCookie($schedule))
        ->postJson(route('study-room.heartbeat'));

    $response->assertOk()->assertJsonPath('stillSeated', true);

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->focus_seconds)->toBe(25 * 60)
        ->and($session->was_completed)->toBeTrue();

    $seat->refresh();
    expect($seat->timer_phase)->toBe(StudyTimerPhase::Focus)
        ->and($seat->timer_started_at)->toBeNull()
        ->and($seat->timer_ends_at)->not->toBeNull();

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'timer.finished' && $event->seat?->code === $seat->code
    );

    // A second heartbeat must not double-record the already-finalized timer.
    $this->withCredentials()
        ->withCookie('student_schedule', heartbeatCookie($schedule))
        ->postJson(route('study-room.heartbeat'))
        ->assertOk();

    expect(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->count())->toBe(1);
});

it('reports the caller no longer seated after their heartbeat has no seat', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', heartbeatCookie($schedule))
        ->postJson(route('study-room.heartbeat'));

    $response->assertOk()->assertJsonPath('stillSeated', false);
});

it('rejects a heartbeat with no schedule cookie', function () {
    $response = $this->withCredentials()->postJson(route('study-room.heartbeat'));

    $response->assertStatus(403);
});
