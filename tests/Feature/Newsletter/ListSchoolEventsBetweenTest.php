<?php

use NouTools\Domains\Shared\Actions\ListSchoolEventsBetween;

beforeEach(function () {
    config(['school-schedules' => [
        '2026C' => [
            ['start' => '2026-08-20', 'end' => '2026-08-31', 'name' => '暑期期末考', 'countdown' => false],
            ['start' => '2026-09-01', 'end' => '2026-09-01', 'name' => '暑期結束', 'countdown' => false],
        ],
        '2026A' => [
            ['start' => '2026-09-05', 'end' => '2026-09-05', 'name' => '115上學期開播', 'countdown' => true],
            ['start' => '2026-09-20', 'end' => '2026-09-30', 'name' => '加退選', 'countdown' => false],
        ],
    ]]);
});

it('returns events overlapping the range across semesters in order', function () {
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
