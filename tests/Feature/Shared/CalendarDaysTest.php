<?php

use App\Models\CalendarDay;
use Carbon\CarbonImmutable;
use NouTools\Domains\Shared\Actions\ListCalendarDaysBetween;
use NouTools\Domains\Shared\Actions\SaveCalendarDays;
use NouTools\Domains\Shared\DataTransferObjects\CalendarDayDTO;

it('lists day overrides within the range in date order', function () {
    CalendarDay::factory()->on('2026-10-10')->labelled('國慶日')->create();
    CalendarDay::factory()->on('2026-09-28')->labelled('教師節')->create();
    CalendarDay::factory()->on('2026-11-01')->create();

    $days = app(ListCalendarDaysBetween::class)('2026-09-01', '2026-10-31');

    expect(array_column($days, 'date'))->toBe(['2026-09-28', '2026-10-10'])
        ->and($days[1]->label)->toBe('國慶日')
        ->and($days[1]->isRed)->toBeTrue();
});

it('replaces only the given year when saving', function () {
    CalendarDay::factory()->on('2026-02-01')->create();
    CalendarDay::factory()->on('2027-01-01')->labelled('元旦')->create();
    CalendarDay::factory()->on('2026-12-31')->create();

    app(SaveCalendarDays::class)(2026, [
        new CalendarDayDTO(CarbonImmutable::parse('2026-10-10'), true, '國慶日'),
        new CalendarDayDTO(CarbonImmutable::parse('2026-10-17'), false, '補班'),
    ]);

    expect(CalendarDay::query()->orderBy('date')->pluck('date')->map->format('Y-m-d')->all())
        ->toBe(['2026-10-10', '2026-10-17', '2027-01-01'])
        ->and(CalendarDay::query()->whereDate('date', '2026-10-17')->first())
        ->is_red->toBeFalse()
        ->label->toBe('補班');
});
