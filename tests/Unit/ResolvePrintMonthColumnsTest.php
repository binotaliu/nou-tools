<?php

use NouTools\Domains\Schedules\Actions\ResolvePrintMonthColumns;
use NouTools\Domains\Schedules\ViewModels\SchedulePrintMonthViewModel;

/**
 * A month with six week rows and the given class dates, each holding the given
 * courses at 19:00 (or 9:00 for the last one, as a morning class would be).
 *
 * @param  array<int, string>  $courseNames
 */
$printMonth = function (int $classDays, array $courseNames): SchedulePrintMonthViewModel {
    return new SchedulePrintMonthViewModel(
        title: '2026 年 11 月',
        weeks: array_fill(0, 6, array_fill(0, 7, null)),
        classDays: array_map(fn (int $day) => [
            'label' => "11/{$day} (一)",
            'courses' => array_map(fn (string $name) => ['name' => $name, 'time' => '19:00'], $courseNames),
        ], range(1, $classDays)),
        examDays: [],
    );
};

it('keeps three columns for an ordinary schedule', function () use ($printMonth) {
    $months = array_fill(0, 5, $printMonth(6, ['心理學', '會計學']));

    expect((new ResolvePrintMonthColumns)($months))->toBe(3);
});

it('switches to four columns when long course lists would push the calendars off the page', function () use ($printMonth) {
    // 5 months at 3 columns is two rows; two busy months make each row too tall.
    $busy = $printMonth(11, ['行政法基本理論', '遊程規劃', '電子商務導論', '效能政府與公共服務']);
    $months = [$busy, $busy, $printMonth(3, ['心理學']), $busy, $printMonth(3, ['心理學'])];

    expect((new ResolvePrintMonthColumns)($months))->toBe(4);
});

it('keeps three columns when four would not be any shorter', function () use ($printMonth) {
    // One row of three months: narrower columns only make the lists wrap more.
    $months = array_fill(0, 3, $printMonth(20, ['商業應用軟體與生活應用實務', '效能政府與公共服務']));

    expect((new ResolvePrintMonthColumns)($months))->toBe(3);
});

it('always uses four columns from seven months on', function () use ($printMonth) {
    $months = array_fill(0, 7, $printMonth(1, ['心理學']));

    expect((new ResolvePrintMonthColumns)($months))->toBe(4);
});

it('handles a schedule without calendars', function () {
    expect((new ResolvePrintMonthColumns)([]))->toBe(3);
});
