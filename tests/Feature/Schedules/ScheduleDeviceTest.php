<?php

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('signs in a visitor whose device token matches', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('device-token')->create();

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'device-token')
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page->where('notifications', fn ($value) => $value !== null));
});

it('treats an unknown token as anonymous', function () {
    StudentSchedule::factory()->create();

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'not-a-real-token')
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page->where('notifications', null));
});

it('treats an expired device as anonymous', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('old-token')->expired()->create();

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'old-token')
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page->where('notifications', null));
});

it('converts a legacy cookie into a device and expires the legacy cookie', function () {
    $schedule = StudentSchedule::factory()->create();

    $response = $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->get(route('settings'));

    $response->assertInertia(fn (Assert $page) => $page->where('notifications', fn ($value) => $value !== null));
    $response->assertCookie(BuildScheduleDeviceCookie::NAME);
    $response->assertCookieExpired('student_schedule');

    assertDatabaseCount('schedule_devices', 1);
    expect(ScheduleDevice::query()->first())
        ->student_schedule_id->toBe($schedule->id)
        ->is_persistent->toBeTrue();
});

it('does not mint a second device when the cookie is read many times in one request', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->get(route('settings'));

    assertDatabaseCount('schedule_devices', 1);
});

it('re-issues the cookie and slides the expiry when the device has not been used for a day', function () {
    $schedule = StudentSchedule::factory()->create();
    $device = ScheduleDevice::factory()->for($schedule)->withToken('stale-token')->create([
        'last_used_at' => now()->subDays(3),
        'expires_at' => now()->addDays(100),
    ]);

    $response = $this->withCookie(BuildScheduleDeviceCookie::NAME, 'stale-token')->get(route('settings'));

    $response->assertCookie(BuildScheduleDeviceCookie::NAME);
    expect($device->refresh()->expires_at->diffInDays(now()->addDays(400), absolute: true))->toBeLessThan(1);
});

it('leaves a recently used device alone', function () {
    $schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($schedule)->withToken('fresh-token')->create();

    $this->withCookie(BuildScheduleDeviceCookie::NAME, 'fresh-token')
        ->get(route('settings'))
        ->assertCookieMissing(BuildScheduleDeviceCookie::NAME);
});

it('stores only a hash of the token', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->get(route('settings'));

    expect(ScheduleDevice::query()->first()->token_hash)->toHaveLength(64);
});

it('prunes devices that expired more than a week ago', function () {
    ScheduleDevice::factory()->create(['expires_at' => now()->subDays(8)]);
    $recent = ScheduleDevice::factory()->create(['expires_at' => now()->subDay()]);

    $this->artisan('model:prune', ['--model' => ScheduleDevice::class])->assertSuccessful();

    expect(ScheduleDevice::query()->pluck('id')->all())->toBe([$recent->id]);
});
