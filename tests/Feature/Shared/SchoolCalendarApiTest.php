<?php

use App\Models\SchoolCalendarEvent;

use function Pest\Laravel\getJson;

it('returns school calendar events for the current semester', function (): void {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-02-01', '2026-02-01')->create(['name' => '學期開始']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-04-25', '2026-04-26')->countdown()->create(['name' => '期中考']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-06-27', '2026-06-28')->countdown()->create(['name' => '期末考']);
    SchoolCalendarEvent::factory()->forTerm('2025A')->create();

    getJson('/api/v1/school-calendar')
        ->assertOk()
        ->assertJsonCount(3);
});

it('returns the expected school calendar event fields', function (): void {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->between('2026-06-27', '2026-06-28')->countdown()->create(['name' => '期末考']);

    $item = getJson('/api/v1/school-calendar')
        ->assertOk()
        ->json('0');

    expect($item)
        ->toHaveKey('name', '期末考')
        ->toHaveKey('startDate', '2026-06-27')
        ->toHaveKey('endDate', '2026-06-28')
        ->toHaveKey('isCountdown', true);
});

it('leaves events not flagged important out of the API', function (): void {
    config(['app.current_semester' => '2025B']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '重要活動']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->minor()->create(['name' => '次要活動']);

    getJson('/api/v1/school-calendar')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', '重要活動');
});

it('returns empty data when the current semester has no calendar', function (): void {
    config(['app.current_semester' => '2099A']);

    getJson('/api/v1/school-calendar')
        ->assertOk()
        ->assertJsonCount(0);
});
