<?php

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerPhase;
use App\Events\CafeUpdated;
use App\Events\StudyRoomUpdated;
use App\Models\CafeOrder;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use App\Notifications\StudyTimerFinished;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use NouTools\Domains\Cafe\Actions\ReleaseIdleCafeSeats;

$cafeTimerCookie = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

it('runs the focus timer on a café seat, answering with the café state', function () use ($cafeTimerCookie) {
    Event::fake([CafeUpdated::class, StudyRoomUpdated::class]);
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '咖啡同學']);
    $seat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    CafeOrder::factory()->for($schedule, 'schedule')->create();
    $cookie = $cafeTimerCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom',
            'minutes' => 30,
            'verb' => StudyActivityVerb::Review->value,
            'subjectCourseId' => null,
        ])
        ->assertOk()
        ->assertJsonPath('state.seats.0.code', $seat->code)
        ->assertJsonPath('state.seats.0.timerPhase', 'focus')
        ->assertJsonMissingPath('state.floors');

    expect($seat->fresh()->timer_phase)->toBe(StudyTimerPhase::Focus);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('study-room.timer.pause'))
        ->assertOk()->assertJsonPath('state.seats.0.pausedAt', fn ($value) => $value !== null);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('study-room.timer.resume'))->assertOk();

    Date::setTestNow(Date::now()->addMinutes(10));

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->deleteJson(route('study-room.timer.stop'))
        ->assertOk()->assertJsonPath('state.seats.0.timerPhase', null);

    expect(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->count())->toBe(2);
    Event::assertDispatched(CafeUpdated::class);
    Event::assertNotDispatched(StudyRoomUpdated::class);
});

it('keeps answering a study-room seat with the study-room state', function () use ($cafeTimerCookie) {
    Event::fake([CafeUpdated::class, StudyRoomUpdated::class]);
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();
    StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    $this->withCredentials()->withCookie('student_schedule', $cafeTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), [
            'mode' => 'custom', 'minutes' => 30, 'verb' => StudyActivityVerb::Review->value, 'subjectCourseId' => null,
        ])
        ->assertOk()->assertJsonStructure(['state' => ['floors']]);

    Event::assertDispatched(StudyRoomUpdated::class);
    Event::assertNotDispatched(CafeUpdated::class);
});

it('sends a café timer-end push to the café page', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();

    $message = (new StudyTimerFinished($seat))->toWebPush($schedule);

    expect($message->toArray()['data']['url'])->toBe(route('cafe.show'));
});

it('will not start the timer on a café seat until something is on the table', function () use ($cafeTimerCookie) {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();
    $seat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    $payload = ['mode' => 'custom', 'minutes' => 30, 'verb' => StudyActivityVerb::Review->value, 'subjectCourseId' => null];

    $this->withCredentials()->withCookie('student_schedule', $cafeTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), $payload)
        ->assertStatus(422)
        ->assertJsonPath('message', '咖啡廳有低消，請先點餐再開始專注吧。');

    expect($seat->fresh()->timer_phase)->toBeNull();

    CafeOrder::factory()->for($schedule, 'schedule')->expired()->create();
    $this->withCredentials()->withCookie('student_schedule', $cafeTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), $payload)->assertStatus(422);

    CafeOrder::factory()->for($schedule, 'schedule')->create();
    $this->withCredentials()->withCookie('student_schedule', $cafeTimerCookie($schedule))
        ->postJson(route('study-room.timer.start'), $payload)->assertOk();
});

it('gives back a café seat nobody ordered at within the grace period', function () {
    Event::fake([CafeUpdated::class]);
    config()->set('cafe.seat.order_grace_seconds', 180);

    $quiet = StudentSchedule::factory()->create();
    $ordered = StudentSchedule::factory()->create();
    $fresh = StudentSchedule::factory()->create();
    $quietSeat = StudyRoomSeat::factory()->cafe()->occupiedBy($quiet)->create();
    $orderedSeat = StudyRoomSeat::factory()->cafe()->occupiedBy($ordered)->create();
    $freshSeat = StudyRoomSeat::factory()->cafe()->occupiedBy($fresh)->create();

    $sat = Date::now()->subMinutes(5);
    $quietSeat->forceFill(['occupied_at' => $sat])->save();
    $orderedSeat->forceFill(['occupied_at' => $sat])->save();
    CafeOrder::factory()->for($ordered, 'schedule')->create(['created_at' => $sat->copy()->addMinute()]);

    expect(app(ReleaseIdleCafeSeats::class)())->toBe(1)
        ->and($quietSeat->fresh()->student_schedule_id)->toBeNull()
        ->and($orderedSeat->fresh()->student_schedule_id)->toBe($ordered->id)
        ->and($freshSeat->fresh()->student_schedule_id)->toBe($fresh->id);

    Event::assertDispatched(CafeUpdated::class, fn (CafeUpdated $event): bool => $event->type === 'seat.unordered-released');
});
