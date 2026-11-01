<?php

use App\Events\CafeUpdated;
use App\Models\CafeSeat;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use NouTools\Domains\Cafe\Actions\ReleaseIdleCafeSeats;
use NouTools\Domains\Cafe\Actions\SyncCafeSeats;

$cafeCookie = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

$profiled = function (): StudentSchedule {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '拿鐵同學']);

    return $schedule;
};

it('syncs the layout from config and leaves occupied seats alone when it shrinks', function () use ($profiled) {
    $sync = app(SyncCafeSeats::class);

    // 6 stools + 4 tables x 2 + 6 communal
    expect($sync())->toBe(20);

    $schedule = $profiled();
    CafeSeat::query()->where('code', 'B06')->update(['student_schedule_id' => $schedule->id]);

    config()->set('cafe.layout.bar_stools', 3);
    $sync();

    expect(CafeSeat::query()->where('code', 'B04')->exists())->toBeFalse()
        ->and(CafeSeat::query()->where('code', 'B06')->exists())->toBeTrue();
});

it('claims a seat, exposes the occupant and broadcasts', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $schedule = $profiled();
    $seat = CafeSeat::factory()->create();

    $this->withCredentials()->withCookie('student_schedule', $cafeCookie($schedule))
        ->postJson(route('cafe.seats.take', $seat))
        ->assertOk()
        ->assertJsonPath('state.seats.0.isYou', true)
        ->assertJsonPath('state.seats.0.nickname', '拿鐵同學')
        ->assertJsonMissingPath('state.seats.0.studentScheduleId');

    expect($seat->fresh()->student_schedule_id)->toBe($schedule->id);
    Event::assertDispatched(CafeUpdated::class, fn (CafeUpdated $event): bool => $event->type === 'seat.taken' && $event->seat?->code === $seat->code);
});

it('refuses a taken seat, a second seat and a visitor without a nickname', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $other = $profiled();
    $me = $profiled();
    $taken = CafeSeat::factory()->occupiedBy($other)->create();
    $free = CafeSeat::factory()->create();
    $another = CafeSeat::factory()->create();

    $this->withCredentials()->withCookie('student_schedule', $cafeCookie($me))
        ->postJson(route('cafe.seats.take', $taken))->assertStatus(409);

    $this->withCredentials()->withCookie('student_schedule', $cafeCookie($me))
        ->postJson(route('cafe.seats.take', $free))->assertOk();

    $this->withCredentials()->withCookie('student_schedule', $cafeCookie($me))
        ->postJson(route('cafe.seats.take', $another))->assertStatus(422);

    $nameless = StudentSchedule::factory()->create();
    $this->withCredentials()->withCookie('student_schedule', $cafeCookie($nameless))
        ->postJson(route('cafe.seats.take', $another))->assertForbidden();

    $this->postJson(route('cafe.seats.take', $another))->assertForbidden();
});

it('leaves a seat idempotently and keeps it alive with a heartbeat', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $schedule = $profiled();
    $seat = CafeSeat::factory()->occupiedBy($schedule)->create();
    $cookie = $cafeCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.heartbeat'))->assertOk()->assertJsonPath('stillSeated', true);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.seat.leave'))->assertOk();
    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.seat.leave'))->assertOk();

    expect($seat->fresh()->student_schedule_id)->toBeNull();

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.heartbeat'))->assertOk()->assertJsonPath('stillSeated', false);

    Event::assertDispatchedTimes(CafeUpdated::class, 1);
});

it('releases seats nobody has seen for the idle window', function () use ($profiled) {
    Event::fake([CafeUpdated::class]);
    $stale = CafeSeat::factory()->occupiedBy($profiled())->create();
    $stale->forceFill(['last_seen_at' => Date::now()->subMinutes(21)])->save();
    $fresh = CafeSeat::factory()->occupiedBy($profiled())->create();

    expect(app(ReleaseIdleCafeSeats::class)())->toBe(1)
        ->and($stale->fresh()->student_schedule_id)->toBeNull()
        ->and($fresh->fresh()->student_schedule_id)->not->toBeNull();
});

it('serves the state with an ETag that ignores who is looking', function () use ($cafeCookie, $profiled) {
    $schedule = $profiled();
    CafeSeat::factory()->occupiedBy($schedule)->create();

    $anonymous = $this->getJson(route('cafe.state'));
    $mine = $this->withCredentials()->withCookie('student_schedule', $cafeCookie($schedule))->getJson(route('cafe.state'));

    expect($mine->headers->get('ETag'))->toBe($anonymous->headers->get('ETag'));

    $this->withHeaders(['If-None-Match' => $anonymous->headers->get('ETag')])
        ->getJson(route('cafe.state'))->assertStatus(304);
});

it('renders the page shell and creates the seats on first visit', function () {
    $this->get(route('cafe.show'))->assertOk();

    expect(CafeSeat::query()->count())->toBe(20);
});
