<?php

use App\Models\SchoolCalendarEvent;
use NouTools\Domains\Shared\Actions\ListSchoolEventsBetween;

beforeEach(function () {
    SchoolCalendarEvent::factory()->forTerm('2026C')->between('2026-08-20', '2026-08-31')->create(['name' => '暑期期末考']);
    SchoolCalendarEvent::factory()->forTerm('2026C')->between('2026-09-01', '2026-09-01')->minor()->create(['name' => '暑期結束']);
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-09-05', '2026-09-05')->countdown()->create(['name' => '115上學期開播']);
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-09-20', '2026-09-30')->create(['name' => '加退選']);
});

it('returns events overlapping the range across semesters in order, important or not', function () {
    $events = app(ListSchoolEventsBetween::class)('2026-08-30', '2026-09-05');

    expect(array_column($events, 'name'))->toBe(['暑期期末考', '暑期結束', '115上學期開播'])
        ->and($events[0])->toBe(['start' => '2026-08-20', 'end' => '2026-08-31', 'name' => '暑期期末考']);
});

it('includes events that started before the range and are still running', function () {
    $events = app(ListSchoolEventsBetween::class)('2026-09-21', '2026-10-04');

    expect(array_column($events, 'name'))->toBe(['加退選']);
});

it('returns nothing for an empty range', function () {
    expect(app(ListSchoolEventsBetween::class)('2026-10-10', '2026-10-20'))->toBe([]);
});
