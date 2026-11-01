<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\Actions;

use App\Enums\ZodiacSign;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Arcade\Support\HoroscopePhrases;
use NouTools\Domains\Arcade\ValueObjects\HoroscopeReading;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Calculates a sign's reading for a Taipei day. Pure and deterministic: the
 * same sign on the same day always gets the same reading.
 *
 * The day's "sky" is real astronomy, but only the mean longitudes of the Sun
 * and the Moon, so they drift a few degrees from the true positions; that is
 * plenty for a game. Each aspect starts at 3 stars and is moved by:
 *
 *  - the Moon's angle to the sign (conjunction, sextile/trine and square);
 *  - the Sun's element ("season") when it matches the sign's;
 *  - the day's ruling planet, which favours one aspect;
 *  - a seeded nudge of -1, 0 or +1 (weights 1:2:1).
 *
 * The seed is a hash of date, sign and `cafe.arcade.horoscope.salt` (the app
 * key by default), so tomorrow's readings cannot be worked out from this
 * public code. 整體運 is the rounded mean of the four others plus its own
 * modifiers.
 */
final readonly class CalculateHoroscope
{
    /** Unix time of J2000 (2000-01-01 12:00 UTC). */
    private const J2000 = 946728000;

    public function __invoke(ZodiacSign $sign, CarbonInterface $day): HoroscopeReading
    {
        $timezone = (string) config('app.schedule_timezone');
        $date = Date::parse($day->format('Y-m-d'), $timezone);
        $dateString = $date->toDateString();

        $daysSinceJ2000 = ($date->copy()->setTime(12, 0)->getTimestamp() - self::J2000) / 86400;
        $sunSign = $this->signAt(280.460 + 0.9856474 * $daysSinceJ2000);
        $moonSign = $this->signAt(218.316 + 13.176396 * $daysSinceJ2000);
        $ruler = HoroscopePhrases::DAY_RULERS[$date->dayOfWeek];

        $random = new Randomizer(new Xoshiro256StarStar(hash(
            'sha256',
            $dateString.'|'.$sign->value.'|'.(config('cafe.arcade.horoscope.salt') ?? config('app.key')),
            true,
        )));

        $moonDistance = ($moonSign->index() - $sign->index() + 12) % 12;
        $moonDistance = min($moonDistance, 12 - $moonDistance);

        $modifiers = ['overall' => 0, 'study' => 0, 'love' => 0, 'wealth' => 0, 'health' => 0];

        match ($moonDistance) {
            0 => [$modifiers['overall'], $modifiers['health']] = [1, 1],
            2, 4 => [$modifiers['overall'], $modifiers['study']] = [1, 1],
            3 => [$modifiers['overall'], $modifiers['study']] = [-1, -1],
            6 => $modifiers['love'] += $random->getInt(0, 1) === 0 ? -1 : 1,
            default => null,
        };

        if ($sunSign->element() === $sign->element()) {
            $modifiers['overall']++;
            $modifiers['health']++;
        }

        match ($ruler) {
            'sun' => $modifiers['overall']++,
            'moon', 'venus' => $modifiers['love']++,
            'mars' => $modifiers['health']++,
            'mercury' => $modifiers['study']++,
            'jupiter' => $modifiers['wealth']++,
            'saturn' => [$modifiers['study'], $modifiers['love']] = [$modifiers['study'] + 1, $modifiers['love'] - 1],
        };

        $scores = [];

        foreach (HoroscopePhrases::CORE_ASPECTS as $aspect) {
            $nudge = [-1, 0, 0, 1][$random->getInt(0, 3)];
            $scores[$aspect] = $this->clamp(3 + $modifiers[$aspect] + $nudge);
        }

        $mean = array_sum($scores) / count($scores);

        $scores = ['overall' => $this->clamp((int) round($mean + $modifiers['overall']))] + $scores;

        $luckyNumber = $random->getInt(1, 99);
        $luckyColor = HoroscopePhrases::COLORS[$random->getInt(0, count(HoroscopePhrases::COLORS) - 1)];
        $luckyItem = HoroscopePhrases::ITEMS[$random->getInt(0, count(HoroscopePhrases::ITEMS) - 1)];
        $luckyDirection = HoroscopePhrases::DIRECTIONS[$random->getInt(0, count(HoroscopePhrases::DIRECTIONS) - 1)];

        // Same element, or the friendly one (fire with air, earth with water).
        $friendlyElement = ($sign->element() + 2) % 4;
        $matches = array_values(array_filter(
            ZodiacSign::cases(),
            fn (ZodiacSign $candidate): bool => $candidate !== $sign
                && in_array($candidate->element(), [$sign->element(), $friendlyElement], true),
        ));
        $matchSign = $matches[$random->getInt(0, count($matches) - 1)];

        return new HoroscopeReading(
            date: $dateString,
            sign: $sign,
            sunSign: $sunSign,
            moonSign: $moonSign,
            ruler: $ruler,
            scores: $scores,
            luckyNumber: $luckyNumber,
            luckyColor: $luckyColor,
            luckyItem: $luckyItem,
            luckyDirection: $luckyDirection,
            matchSign: $matchSign,
            advice: $this->advice($scores, $random),
        );
    }

    /**
     * One line for the strongest aspect and one for the weakest. A seeded
     * shuffle first, so ties are broken the same way every time.
     *
     * @param  array<string, int>  $scores
     * @return array<int, string>
     */
    private function advice(array $scores, Randomizer $random): array
    {
        $aspects = $random->shuffleArray(HoroscopePhrases::CORE_ASPECTS);
        usort($aspects, fn (string $a, string $b): int => $scores[$b] <=> $scores[$a]);

        $strongest = $aspects[0];
        $weakest = $aspects[count($aspects) - 1];

        $pick = fn (string $aspect, string $tier): string => HoroscopePhrases::ADVICE[$aspect][$tier][$random->getInt(0, 2)];

        return [
            $pick($strongest, $scores[$strongest] >= 4 ? 'high' : 'mid'),
            $pick($weakest, $scores[$weakest] <= 2 ? 'low' : 'mid'),
        ];
    }

    private function signAt(float $longitude): ZodiacSign
    {
        $normalised = fmod(fmod($longitude, 360) + 360, 360);

        return ZodiacSign::fromIndex((int) floor($normalised / 30));
    }

    private function clamp(int $stars): int
    {
        return max(1, min(5, $stars));
    }
}
