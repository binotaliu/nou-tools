<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\ViewModels;

use App\Models\ArcadePlay;
use NouTools\Domains\Arcade\Support\HoroscopePhrases;
use NouTools\Domains\Arcade\ValueObjects\HoroscopeReading;
use Spatie\LaravelData\Data;

final class HoroscopeReadingViewModel extends Data
{
    /**
     * @param  array<int, array{key: string, label: string, stars: int}>  $scores  overall first
     * @param  array{name: string, hex: string}  $luckyColor
     * @param  array{value: string, label: string, symbol: string}  $matchSign
     * @param  array<int, string>  $advice
     */
    public function __construct(
        public string $date,
        public string $sign,
        public string $signLabel,
        public string $symbol,
        public string $sunSignLabel,
        public string $moonSignLabel,
        public string $rulerLabel,
        public array $scores,
        public int $luckyNumber,
        public array $luckyColor,
        public string $luckyItem,
        public string $luckyDirection,
        public array $matchSign,
        public array $advice,
    ) {}

    public static function fromPlay(ArcadePlay $play): self
    {
        $reading = HoroscopeReading::restore($play->result);

        $scores = [];

        foreach ($reading->scores as $key => $stars) {
            $scores[] = [
                'key' => $key,
                'label' => HoroscopePhrases::ASPECT_LABELS[$key],
                'stars' => $stars,
            ];
        }

        return new self(
            date: $reading->date,
            sign: $reading->sign->value,
            signLabel: $reading->sign->label(),
            symbol: $reading->sign->symbol(),
            sunSignLabel: $reading->sunSign->label(),
            moonSignLabel: $reading->moonSign->label(),
            rulerLabel: HoroscopePhrases::RULER_LABELS[$reading->ruler],
            scores: $scores,
            luckyNumber: $reading->luckyNumber,
            luckyColor: $reading->luckyColor,
            luckyItem: $reading->luckyItem,
            luckyDirection: $reading->luckyDirection,
            matchSign: [
                'value' => $reading->matchSign->value,
                'label' => $reading->matchSign->label(),
                'symbol' => $reading->matchSign->symbol(),
            ],
            advice: $reading->advice,
        );
    }
}
