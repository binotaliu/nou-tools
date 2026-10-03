<?php

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

$signedInAs = function (StudentSchedule $schedule, string $token = 'this-device'): void {
    ScheduleDevice::factory()->for($schedule)->withToken($token)->create();
    test()->withCookie(BuildScheduleDeviceCookie::NAME, $token);
};

it('revokes this device, clears the cookie and goes back to settings', function () use ($signedInAs) {
    $schedule = StudentSchedule::factory()->create();
    $signedInAs($schedule);

    $response = $this->delete(route('schedules.device.destroy'));

    $response->assertRedirect(route('settings'));
    $response->assertCookieExpired(BuildScheduleDeviceCookie::NAME);
    assertDatabaseCount('schedule_devices', 0);
});

it('leaves the schedule\'s other devices signed in', function () use ($signedInAs) {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('other-device')->create();
    $signedInAs($schedule);

    $this->delete(route('schedules.device.destroy'));

    $this->flushSession();
    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'other-device')
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page->where('notifications', fn ($value) => $value !== null));
});

it('is anonymous afterwards even if the old cookie is replayed', function () use ($signedInAs) {
    $schedule = StudentSchedule::factory()->create();
    $signedInAs($schedule);

    $this->delete(route('schedules.device.destroy'));

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'this-device')
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page->where('notifications', null));
});

it('removes only this browser\'s push subscription', function () use ($signedInAs) {
    $schedule = StudentSchedule::factory()->create();
    $schedule->updatePushSubscription('https://push.example/this-browser', 'key', 'token');
    $schedule->updatePushSubscription('https://push.example/other-browser', 'key', 'token');
    $signedInAs($schedule);

    $this->delete(route('schedules.device.destroy'), ['pushEndpoint' => 'https://push.example/this-browser']);

    assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://push.example/this-browser']);
    assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://push.example/other-browser']);
});

it('does not touch the notification opt-ins', function () use ($signedInAs) {
    $schedule = StudentSchedule::factory()->create();
    $schedule->notify_on_class_start = true;
    $schedule->save();
    $signedInAs($schedule);

    $this->delete(route('schedules.device.destroy'), ['pushEndpoint' => 'https://push.example/this-browser']);

    expect($schedule->refresh()->notify_on_class_start)->toBeTrue();
});

it('releases the study room seat the schedule holds', function () use ($signedInAs) {
    app(SyncStudyRoomSeats::class)();
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::query()->where('floor', 1)->where('kind', 'solo')->where('seat_number', 1)->first();
    $seat->update(['student_schedule_id' => $schedule->id, 'occupied_at' => now(), 'last_seen_at' => now()]);
    $signedInAs($schedule);

    $this->delete(route('schedules.device.destroy'));

    expect($seat->refresh()->student_schedule_id)->toBeNull();
});

it('still clears the cookie for a visitor who is already signed out', function () {
    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'stale')
        ->delete(route('schedules.device.destroy'))
        ->assertRedirect(route('settings'))
        ->assertCookieExpired(BuildScheduleDeviceCookie::NAME);
});

it('signs out a visitor who still has the legacy cookie without leaving a device behind', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->delete(route('schedules.device.destroy'));

    $response->assertCookieExpired(BuildScheduleDeviceCookie::NAME);
    $response->assertCookieExpired('student_schedule');
    assertDatabaseCount('schedule_devices', 0);
});

it('rejects an oversized push endpoint', function () {
    $this->delete(route('schedules.device.destroy'), ['pushEndpoint' => str_repeat('a', 2049)])
        ->assertSessionHasErrors('pushEndpoint');
});
