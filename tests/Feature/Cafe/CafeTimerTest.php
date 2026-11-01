<?php

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerPhase;
use App\Events\CafeUpdated;
use App\Events\StudyRoomUpdated;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use App\Notifications\StudyTimerFinished;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;

$cafeTimerCookie = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

it('runs the focus timer on a café seat, answering with the café state', function () use ($cafeTimerCookie) {
    Event::fake([CafeUpdated::class, StudyRoomUpdated::class]);
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '咖啡同學']);
    $seat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
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
