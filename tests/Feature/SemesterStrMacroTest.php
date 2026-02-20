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
