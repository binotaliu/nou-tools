<?php

use App\Events\StudyRoomUpdated;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Event;

it('releases a seat occupied for 6 minutes with no timer ever started', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    $this->travel(6)->minutes();

    $this->artisan('study-room:release-seats-without-timer')
        ->expectsOutputToContain('已釋放 1 個未計時座位')
        ->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull();

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'seat.no-timer-released' && $event->seat?->code === $seat->code
    );
});

it('leaves a seat occupied for only 4 minutes untouched', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    $this->travel(4)->minutes();

    $this->artisan('study-room:release-seats-without-timer')->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBe($schedule->id);
});

it('leaves a long-occupied seat with a running timer untouched', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create([
        'timer_started_at' => now(),
    ]);

    $this->travel(6)->minutes();

    $this->artisan('study-room:release-seats-without-timer')->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBe($schedule->id);
});

it('does not immediately release a seat right after its timer stops, even if occupied long ago', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    // Studied for an hour, then stopped the timer a moment ago.
    $this->travel(60)->minutes();
    $seat->update(['timer_started_at' => null, 'no_timer_since' => now()]);

    $this->artisan('study-room:release-seats-without-timer')->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBe($schedule->id);
});

it('releases a seat 6 minutes after its timer stopped and was never restarted', function () {
    Event::fake([StudyRoomUpdated::class]);

    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    $this->travel(60)->minutes();
    $seat->update(['timer_started_at' => null, 'no_timer_since' => now()]);
    $this->travel(6)->minutes();

    $this->artisan('study-room:release-seats-without-timer')
        ->expectsOutputToContain('已釋放 1 個未計時座位')
        ->assertExitCode(0);

    $seat->refresh();
    expect($seat->student_schedule_id)->toBeNull();
});

it('is a no-op on an empty room', function () {
    $this->artisan('study-room:release-seats-without-timer')
        ->expectsOutputToContain('沒有需要釋放的未計時座位')
        ->assertExitCode(0);
});
