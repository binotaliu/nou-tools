<?php

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerPhase;
use App\Events\StudyRoomUpdated;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Event;

it('releases a seat idle for 6 minutes, crediting focus seconds only up to last_seen_at', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = StudentSchedule::factory()->create();
    $timerStartedAt = now();

    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create([
        'activity_verb' => StudyActivityVerb::Review,
        'timer_mode' => 'pomodoro',
        'timer_phase' => StudyTimerPhase::Focus,
        'timer_started_at' => $timerStartedAt,
        'timer_ends_at' => $timerStartedAt->copy()->addMinutes(25),
    ]);

    // Browser died: last_seen_at stops updating 2 minutes into the focus
    // session, then 6 more minutes pass with no further heartbeat.
    $this->travel(2)->minutes();
    $seat->update(['last_seen_at' => now()]);
    $this->travel(6)->minutes();

    $this->artisan('study-room:release-idle-seats')
        ->expectsOutputToContain('已釋放 1 個閒置座位')
        ->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull()
        ->and($seat->timer_phase)->toBeNull();

    $session = StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->sole();
    expect($session->focus_seconds)->toBe(120)
        ->and($session->was_completed)->toBeFalse();

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'seat.idle-released' && $event->seat?->code === $seat->code
    );
});

it('leaves a seat idle for only 4 minutes untouched', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create(['last_seen_at' => now()]);

    $this->travel(4)->minutes();

    $this->artisan('study-room:release-idle-seats')->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBe($schedule->id);
});

it('is a no-op on an empty room', function () {
    $this->artisan('study-room:release-idle-seats')
        ->expectsOutputToContain('沒有需要釋放的閒置座位')
        ->assertExitCode(0);

    expect(StudyRoomSession::query()->count())->toBe(0);
});
