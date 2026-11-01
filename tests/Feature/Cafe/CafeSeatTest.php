<?php

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Events\CafeUpdated;
use App\Models\CafeOrder;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use NouTools\Domains\Cafe\Actions\ReleaseIdleCafeSeats;
use NouTools\Domains\Cafe\Actions\SyncCafeSeats;
use NouTools\Domains\StudyRoom\Actions\ReleaseIdleSeats;

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
    expect($sync())->toBe(20)
        ->and(StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'W2-1')->value('label'))->toBe('靠牆 2 號桌 1 位');

    $schedule = $profiled();
    StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'B06')->update(['student_schedule_id' => $schedule->id]);

    config()->set('cafe.layout.bar_stools', 3);
    $sync();

    expect(StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'B04')->exists())->toBeFalse()
        ->and(StudyRoomSeat::query()->where('room', 'cafe')->where('code', 'B06')->exists())->toBeTrue();
});

it('claims a seat, exposes the occupant and broadcasts', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $schedule = $profiled();
    $seat = StudyRoomSeat::factory()->cafe()->create();

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
    $taken = StudyRoomSeat::factory()->cafe()->occupiedBy($other)->create();
    $free = StudyRoomSeat::factory()->cafe()->create();
    $another = StudyRoomSeat::factory()->cafe()->create();

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
    $seat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
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
    $stale = StudyRoomSeat::factory()->cafe()->occupiedBy($profiled())->create();
    $stale->forceFill(['last_seen_at' => Date::now()->subMinutes(21)])->save();
    $fresh = StudyRoomSeat::factory()->cafe()->occupiedBy($profiled())->create();

    expect(app(ReleaseIdleCafeSeats::class)())->toBe(1)
        ->and($stale->fresh()->student_schedule_id)->toBeNull()
        ->and($fresh->fresh()->student_schedule_id)->not->toBeNull();
});

it('serves the state with an ETag that ignores who is looking', function () use ($cafeCookie, $profiled) {
    $schedule = $profiled();
    StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();

    $anonymous = $this->getJson(route('cafe.state'));
    $mine = $this->withCredentials()->withCookie('student_schedule', $cafeCookie($schedule))->getJson(route('cafe.state'));

    expect($mine->headers->get('ETag'))->toBe($anonymous->headers->get('ETag'));

    $this->withHeaders(['If-None-Match' => $anonymous->headers->get('ETag')])
        ->getJson(route('cafe.state'))->assertStatus(304);
});

it('renders the page shell and creates the seats on first visit', function () {
    $this->get(route('cafe.show'))->assertOk();

    expect(StudyRoomSeat::query()->where('room', 'cafe')->count())->toBe(20);
});

it('holds one seat across the study room and the café', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $schedule = $profiled();
    $cookie = $cafeCookie($schedule);
    $studySeat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();
    $cafeSeat = StudyRoomSeat::factory()->cafe()->create();

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.seats.take', $cafeSeat))
        ->assertStatus(422)
        ->assertJsonPath('message', '你正坐在自習室，請先離座再到咖啡廳。');

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('study-room.seats.take', $studySeat))->assertStatus(422);

    $studySeat->forceFill(['student_schedule_id' => null])->save();
    $cafeSeat->forceFill(['student_schedule_id' => $schedule->id])->save();
    $free = StudyRoomSeat::factory()->create();

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('study-room.seats.take', $free))
        ->assertStatus(422)
        ->assertJsonPath('message', '你正坐在咖啡廳，請先離座再到自習室。');

    // The café's routes only take café seats, the study room's only study seats.
    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.seats.take', $free))->assertNotFound();
});

it('does not let a café heartbeat or a study-room sweep touch the other room', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $schedule = $profiled();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    $this->withCredentials()->withCookie('student_schedule', $cafeCookie($schedule))
        ->postJson(route('cafe.heartbeat'))->assertOk()->assertJsonPath('stillSeated', false);

    $cafe = StudyRoomSeat::factory()->cafe()->occupiedBy($profiled())->create();
    $cafe->forceFill(['last_seen_at' => Date::now()->subHours(2)])->save();

    // 20-minute café threshold vs the study room's hour: each sweep owns its room.
    expect(app(ReleaseIdleSeats::class)())->toBe(0)
        ->and($seat->fresh()->student_schedule_id)->toBe($schedule->id)
        ->and($cafe->fresh()->student_schedule_id)->not->toBeNull();
});

it('throws away dine-in drinks and records the session when the café seat is left or idles out', function () use ($cafeCookie, $profiled) {
    Event::fake([CafeUpdated::class]);
    $schedule = $profiled();
    $cookie = $cafeCookie($schedule);
    $seat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    $seat->forceFill([
        'timer_mode' => StudyTimerMode::Custom,
        'timer_phase' => StudyTimerPhase::Focus,
        'timer_started_at' => Date::now()->subMinutes(30),
        'activity_started_at' => Date::now()->subMinutes(30),
        'timer_ends_at' => Date::now()->addMinutes(30),
        'activity_verb' => StudyActivityVerb::Reading,
        'subject_label' => '閱讀',
    ])->save();
    $dineIn = CafeOrder::factory()->for($schedule, 'schedule')->create(['to_go' => false]);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.seat.leave'))->assertOk();

    expect($dineIn->fresh()->expires_at->isFuture())->toBeFalse()
        ->and($schedule->fresh()->activeCafeOrder)->toBeNull()
        ->and($seat->fresh()->timer_mode)->toBeNull()
        ->and(StudyRoomSession::query()->where('student_schedule_id', $schedule->id)->count())->toBe(1);

    // Takeaway leaves with them, through a manual leave and an idle release alike.
    $other = $profiled();
    $idle = StudyRoomSeat::factory()->cafe()->occupiedBy($other)->create();
    $idle->forceFill(['last_seen_at' => Date::now()->subMinutes(21)])->save();
    $toGo = CafeOrder::factory()->for($other, 'schedule')->create(['to_go' => true]);
    $tossed = CafeOrder::factory()->for($other, 'schedule')->create(['to_go' => false]);

    app(ReleaseIdleCafeSeats::class)();

    expect($toGo->fresh()->expires_at->isFuture())->toBeTrue()
        ->and($tossed->fresh()->expires_at->isFuture())->toBeFalse();
});
