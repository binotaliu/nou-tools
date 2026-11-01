<?php

use App\Enums\ZodiacSign;
use Illuminate\Support\Carbon;
use NouTools\Domains\Arcade\Actions\CalculateHoroscope;

it('gives the same sign the same reading on the same day', function () {
    $calculate = app(CalculateHoroscope::class);
    $day = Carbon::parse('2026-11-03', 'Asia/Taipei');

    expect($calculate(ZodiacSign::Leo, $day)->toArray())
        ->toBe($calculate(ZodiacSign::Leo, $day->copy()->setTime(23, 59))->toArray());
});

it('changes the reading from one day or sign to the next', function () {
    $calculate = app(CalculateHoroscope::class);
    $day = Carbon::parse('2026-11-03', 'Asia/Taipei');

    $readings = collect(range(0, 29))
        ->map(fn (int $offset): array => $calculate(ZodiacSign::Virgo, $day->copy()->addDays($offset))->toArray())
        ->map(fn (array $reading): string => json_encode($reading));

    $otherSigns = collect(ZodiacSign::cases())
        ->map(fn (ZodiacSign $sign): string => json_encode($calculate($sign, $day)->toArray()));

    expect($readings->unique()->count())->toBeGreaterThan(25)
        ->and($otherSigns->unique()->count())->toBe(12);
});

it('keeps every score between one and five stars and the lucky pieces in range', function () {
    $calculate = app(CalculateHoroscope::class);
    $day = Carbon::parse('2026-01-01', 'Asia/Taipei');

    foreach (range(0, 59) as $offset) {
        foreach (ZodiacSign::cases() as $sign) {
            $reading = $calculate($sign, $day->copy()->addDays($offset));

            expect(array_keys($reading->scores))->toBe(['overall', 'study', 'love', 'wealth', 'health'])
                ->and($reading->scores)->each->toBeBetween(1, 5)
                ->and($reading->luckyNumber)->toBeBetween(1, 99)
                ->and($reading->matchSign)->not->toBe($sign)
                ->and($reading->advice)->toHaveCount(2);
        }
    }
});

it('puts the sun and the moon in plausible signs', function (string $date, ZodiacSign $sun) {
    $reading = app(CalculateHoroscope::class)(ZodiacSign::Aries, Carbon::parse($date, 'Asia/Taipei'));

    expect($reading->sunSign)->toBe($sun);
})->with([
    ['2026-01-01', ZodiacSign::Capricorn],
    ['2026-04-01', ZodiacSign::Aries],
    ['2026-07-01', ZodiacSign::Cancer],
    ['2026-10-10', ZodiacSign::Libra],
]);

it('moves the moon through the zodiac in about two and a half days per sign', function () {
    $calculate = app(CalculateHoroscope::class);
    $day = Carbon::parse('2026-11-03', 'Asia/Taipei');

    $moonSigns = collect(range(0, 27))
        ->map(fn (int $offset): ZodiacSign => $calculate(ZodiacSign::Aries, $day->copy()->addDays($offset))->moonSign);

    expect($moonSigns->unique()->count())->toBe(12);
});

it('uses the salt so readings cannot be precomputed', function () {
    $calculate = app(CalculateHoroscope::class);
    $day = Carbon::parse('2026-11-03', 'Asia/Taipei');

    config()->set('cafe.arcade.horoscope.salt', 'one');
    $first = collect(ZodiacSign::cases())->map(fn ($sign) => json_encode($calculate($sign, $day)->toArray()));

    config()->set('cafe.arcade.horoscope.salt', 'two');
    $second = collect(ZodiacSign::cases())->map(fn ($sign) => json_encode($calculate($sign, $day)->toArray()));

    expect($first->all())->not->toBe($second->all());
});
