<?php

use App\Data\StudentScheduleCookie;
use App\Models\StudentSchedule;
use Illuminate\Http\Request;

it('parses student_schedule cookie via request macro and returns data class', function () {
    $schedule = StudentSchedule::create([
        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
        'name' => 'My Saved Schedule',
    ]);

    $cookieValue = json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);

    $request = Request::create('/', 'GET', [], ['student_schedule' => $cookieValue]);

    $result = $request->studentScheduleFromCookie();

    expect($result)->toBeInstanceOf(StudentScheduleCookie::class);
    expect($result?->id)->toBe($schedule->id);
    expect($result?->uuid)->toBe($schedule->uuid);
    expect($result?->token)->toBe((string) $schedule->getRouteKey());
    expect($result?->name)->toBe($schedule->name);
});
