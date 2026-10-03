<?php

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;

use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('shares an anonymous state with a visitor who is not signed in', function () {
    $this->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scheduleDevice.signedIn', false)
            ->where('scheduleDevice.fingerprint', null));
});

it('shares only a fingerprint of a remembered device, never its token', function () {
    $schedule = StudentSchedule::factory()->create();
    $device = ScheduleDevice::factory()->for($schedule)->withToken('remembered')->create();

    $response = $this->withCookie(BuildScheduleDeviceCookie::NAME, 'remembered')->get(route('settings'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('scheduleDevice.signedIn', true)
        ->where('scheduleDevice.fingerprint', ScheduleDevice::fingerprint($device->token_hash)));
    expect($response->getContent())->not->toContain('remembered"');
});

it('shares no fingerprint for a device that lasts only for the browser session', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('session-only')->create(['is_persistent' => false]);

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'session-only')->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('scheduleDevice.signedIn', true)
            ->where('scheduleDevice.fingerprint', null));
});

it('hands a remembered device its own token and fingerprint to back up', function () {
    $schedule = StudentSchedule::factory()->create();
    $device = ScheduleDevice::factory()->for($schedule)->withToken('remembered')->create();

    $this->withCredentials()->withCookie(BuildScheduleDeviceCookie::NAME, 'remembered')
        ->postJson(route('schedules.device.backup'))
        ->assertOk()
        ->assertExactJson(['token' => 'remembered', 'fingerprint' => ScheduleDevice::fingerprint($device->token_hash)]);
});

it('hands out nothing to back up for a session-only device or a visitor', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('session-only')->create(['is_persistent' => false]);

    $this->withCredentials()->withCookie(BuildScheduleDeviceCookie::NAME, 'session-only')
        ->postJson(route('schedules.device.backup'))
        ->assertNoContent();

    $this->postJson(route('schedules.device.backup'))->assertNoContent();
});

it('restores the cookie for a valid remembered token and slides its expiry', function () {
    $schedule = StudentSchedule::factory()->create();
    $device = ScheduleDevice::factory()->for($schedule)->withToken('from-storage')->create([
        'expires_at' => now()->addDays(10),
    ]);

    $response = $this->postJson(route('schedules.device.restore'), ['token' => 'from-storage']);

    $response->assertOk()->assertJson(['restored' => true]);
    expect($response->getCookie(BuildScheduleDeviceCookie::NAME)->getValue())->toBe('from-storage');
    expect($device->refresh()->expires_at->gt(now()->addDays(300)))->toBeTrue();
});

it('refuses to restore an unknown, expired, revoked or session-only token', function (string $token) {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('expired')->expired()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('session-only')->create(['is_persistent' => false]);

    $this->postJson(route('schedules.device.restore'), ['token' => $token])
        ->assertStatus(422)
        ->assertJson(['restored' => false])
        ->assertCookieMissing(BuildScheduleDeviceCookie::NAME);
})->with(['unknown', 'expired', 'session-only']);

it('cannot bring a signed-out device back', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('signed-out')->create();

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'signed-out')->delete(route('schedules.device.destroy'));

    $this->postJson(route('schedules.device.restore'), ['token' => 'signed-out'])->assertStatus(422);
});

it('requires a token to restore', function () {
    $this->postJson(route('schedules.device.restore'), [])->assertJsonValidationErrors('token');
});
