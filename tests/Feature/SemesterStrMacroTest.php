<?php

use Illuminate\Support\Str;

it('formats semester codes (full)', function () {
    expect(Str::toSemesterDisplay('2025B'))->toBe('114 學年度下學期')
        ->and(Str::toSemesterDisplay('2025A'))->toBe('114 學年度上學期')
        ->and(Str::toSemesterDisplay('2025C'))->toBe('114 學年度暑期');
});

it('returns original input if format invalid', function () {
    expect(Str::toSemesterDisplay('INVALID'))->toBe('INVALID');
});

it('sorts semesters newest first with C before A before B within a year', function () {
    $sorted = collect(['2025B', '2026A', '2026C', '2026B', '2025A'])
        ->sortByDesc(fn (string $code): string => Str::toSemesterSortKey($code))
        ->values()
        ->all();

    expect($sorted)->toBe(['2026B', '2026A', '2026C', '2025B', '2025A']);
});
