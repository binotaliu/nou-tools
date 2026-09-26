<?php

use App\Models\StudentSchedule;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('gives the schedule page its backup link and a scalable QR code', function () {
    $schedule = StudentSchedule::factory()->create(['name' => '我的備份']);

    get(route('schedules.show', $schedule))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Schedule/Show')
            ->where('backup.name', '我的備份')
            ->where('backup.url', route('schedules.show', $schedule))
            ->where('backup.qrCodeSvg', fn (string $svg) => str_contains($svg, '<svg viewBox="0 0')));
});

it('names an unnamed schedule in its backup', function () {
    $schedule = StudentSchedule::factory()->create(['name' => '']);

    get(route('schedules.show', $schedule))
        ->assertInertia(fn (Assert $page) => $page->where('backup.name', '我的課表'));
});
