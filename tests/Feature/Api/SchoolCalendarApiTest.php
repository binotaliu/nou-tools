<?php

use function Pest\Laravel\getJson;

it('returns school calendar events for the current semester', function (): void {
    config([
        'app.current_semester' => '2025B',
        'school-schedules.2025B' => [
            ['name' => '學期開始', 'start' => '2026-02-01', 'end' => '2026-02-01', 'countdown' => false],
            ['name' => '期中考', 'start' => '2026-04-25', 'end' => '2026-04-26', 'countdown' => true],
            ['name' => '期末考', 'start' => '2026-06-27', 'end' => '2026-06-28', 'countdown' => true],
        ],
    ]);

    getJson('/api/v1/school-calendar')
        ->assertOk()
        ->assertJsonCount(3);
});

it('returns the expected school calendar event fields', function (): void {
    config([
        'app.current_semester' => '2025B',
        'school-schedules.2025B' => [
            ['name' => '期末考', 'start' => '2026-06-27', 'end' => '2026-06-28', 'countdown' => true],
        ],
    ]);

    $item = getJson('/api/v1/school-calendar')
        ->assertOk()
        ->json('0');

    expect($item)
        ->toHaveKey('name', '期末考')
        ->toHaveKey('startDate', '2026-06-27')
        ->toHaveKey('endDate', '2026-06-28')
        ->toHaveKey('isCountdown', true);
});

it('returns empty data when the current semester has no calendar configured', function (): void {
    config(['app.current_semester' => '2099A', 'school-schedules' => []]);

    getJson('/api/v1/school-calendar')
        ->assertOk()
        ->assertJsonCount(0);
});
