<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

it('masks the schedule token in data-analytics-page for schedules.show', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Analytics Test',
    ]);

    $response = get(route('schedules.show', $schedule));

    $response->assertSuccessful();
    $response->assertSee('data-analytics-page="/schedules/:schedule"', false);
    $response->assertDontSee((string) $schedule->uuid);
});

it('masks the schedule token in data-analytics-page for schedules.customize', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Analytics Test',
    ]);

    $response = get(route('schedules.customize', $schedule));

    $response->assertSuccessful();
    $response->assertSee('data-analytics-page="/schedules/:schedule/customize"', false);
    $response->assertDontSee((string) $schedule->uuid);
});

it('masks the schedule token in data-analytics-page for schedules.announcement-preferences', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Analytics Test',
    ]);

    $response = get(route('schedules.announcement-preferences', $schedule));

    $response->assertSuccessful();
    $response->assertSee('data-analytics-page="/schedules/:schedule/announcement-preferences"', false);
    $response->assertDontSee((string) $schedule->uuid);
});

it('shares the same masked path as an analyticsPage Inertia prop, so client-side navigations can track it', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Analytics Test',
    ]);

    $response = get(route('schedules.show', $schedule));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->where('analyticsPage', '/schedules/:schedule'));
});
