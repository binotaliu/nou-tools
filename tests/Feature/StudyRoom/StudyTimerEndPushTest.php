<?php

use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Notifications\StudyTimerFinished;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use NouTools\Domains\StudyRoom\Actions\SendStudyTimerEndPushes;

/**
 * Seats a student whose countdown ran out `$endedSecondsAgo` seconds ago,
 * opted in and subscribed unless told otherwise.
 *
 * @return array{0: StudentSchedule, 1: StudyRoomSeat}
 */
$expiredTimerSeat = function (
    int $endedSecondsAgo = 3,
    bool $notifyOnTimerEnd = true,
    bool $subscribed = true,
    StudyTimerPhase $phase = StudyTimerPhase::Focus,
    array $seatOverrides = [],
): array {
    $schedule = StudentSchedule::factory()->create();

    StudyRoomProfile::factory()->for($schedule, 'schedule')->create([
        'notify_on_timer_end' => $notifyOnTimerEnd,
    ]);

    if ($subscribed) {
        $schedule->updatePushSubscription(
            endpoint: 'https://fcm.googleapis.com/fcm/send/'.$schedule->id,
            key: 'p256dh-key',
            token: 'auth-token',
        );
    }

    $endsAt = Date::now()->subSeconds($endedSecondsAgo);

    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create([
        'timer_mode' => StudyTimerMode::Pomodoro,
        'timer_phase' => $phase,
        'timer_round' => 1,
        'timer_started_at' => $endsAt->copy()->subMinutes(25),
        'activity_started_at' => $endsAt->copy()->subMinutes(25),
        'timer_ends_at' => $endsAt,
        ...$seatOverrides,
    ]);

    return [$schedule, $seat];
};

beforeEach(function () {
    Notification::fake();
});

it('notifies a student whose focus timer just ran out', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat();

    expect(app(SendStudyTimerEndPushes::class)())->toBe(1);

    Notification::assertSentTo($schedule, StudyTimerFinished::class);
});

it('notifies when a break timer runs out, not just a focus one', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(phase: StudyTimerPhase::Break);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(1);

    Notification::assertSentTo($schedule, StudyTimerFinished::class);
});

it('stamps the seat so a second sweep stays quiet', function () use ($expiredTimerSeat) {
    [$schedule, $seat] = $expiredTimerSeat();

    expect(app(SendStudyTimerEndPushes::class)())->toBe(1);

    expect($seat->refresh()->timer_end_notified_at)->not->toBeNull();

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertSentToTimes($schedule, StudyTimerFinished::class, 1);
});

it('stays quiet while the timer is paused', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(seatOverrides: ['paused_at' => Date::now()->subMinute()]);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertNothingSentTo($schedule);
});

it('stays quiet for a student who has not opted in', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(notifyOnTimerEnd: false);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertNothingSentTo($schedule);
});

it('stays quiet for a student with no push subscription', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(subscribed: false);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertNothingSentTo($schedule);
});

it('stays quiet for a count-up timer, which has no end', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(seatOverrides: [
        'timer_mode' => StudyTimerMode::CountUp,
        'timer_ends_at' => null,
        'timer_round' => null,
    ]);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertNothingSentTo($schedule);
});

it('stays quiet about a timer that ran out long ago', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(endedSecondsAgo: 60 * 30);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertNothingSentTo($schedule);
});

it('stays quiet for a seat nobody is sitting in', function () use ($expiredTimerSeat) {
    [$schedule] = $expiredTimerSeat(seatOverrides: ['student_schedule_id' => null]);

    expect(app(SendStudyTimerEndPushes::class)())->toBe(0);

    Notification::assertNothingSentTo($schedule);
});

it('notifies again once the next round has started', function () use ($expiredTimerSeat) {
    [$schedule, $seat] = $expiredTimerSeat();

    expect(app(SendStudyTimerEndPushes::class)())->toBe(1);

    // What StartNextRound writes: a new end, and the marker cleared.
    // Set directly rather than via update() — these models deliberately
    // have no mass-assignable attributes.
    $seat->timer_ends_at = Date::now()->subSeconds(2);
    $seat->timer_end_notified_at = null;
    $seat->timer_round = 2;
    $seat->saveOrFail();

    expect(app(SendStudyTimerEndPushes::class)())->toBe(1);

    Notification::assertSentToTimes($schedule, StudyTimerFinished::class, 2);
});

/**
 * The marker has to be cleared by everything that gives the seat a fresh
 * `timer_ends_at`, or the student is told about their first expiry and
 * never again. Driven through the real endpoints so the assertions bind
 * to the actions rather than to a hand-written seat state.
 */
it('clears the marker when the next timer starts', function (string $route, string $method) use ($expiredTimerSeat) {
    [$schedule, $seat] = $expiredTimerSeat();

    expect(app(SendStudyTimerEndPushes::class)())->toBe(1);
    expect($seat->refresh()->timer_end_notified_at)->not->toBeNull();

    $this->withCredentials()
        ->withCookie('student_schedule', json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]))
        ->{$method}(route($route))
        ->assertOk();

    expect($seat->refresh()->timer_end_notified_at)->toBeNull();
})->with([
    'starting the break' => ['study-room.timer.break', 'postJson'],
    'stopping the timer' => ['study-room.timer.stop', 'deleteJson'],
]);
