<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\ValueObjects;

use App\Enums\ZodiacSign;

/**
 * One calculated reading. `toArray()` is what `arcade_plays.result` stores, so
 * a reading someone paid for never changes when the algorithm is tuned.
 */
final readonly class HoroscopeReading
{
    /**
     * @param  array<string, int>  $scores  aspect key => 1..5 stars
     * @param  array{name: string, hex: string}  $luckyColor
     * @param  array<int, string>  $advice
     */
    public function __construct(
        public string $date,
        public ZodiacSign $sign,
        public ZodiacSign $sunSign,
        public ZodiacSign $moonSign,
        public string $ruler,
        public array $scores,
        public int $luckyNumber,
        public array $luckyColor,
        public string $luckyItem,
        public string $luckyDirection,
        public ZodiacSign $matchSign,
        public array $advice,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'sign' => $this->sign->value,
            'sunSign' => $this->sunSign->value,
            'moonSign' => $this->moonSign->value,
            'ruler' => $this->ruler,
            'scores' => $this->scores,
            'luckyNumber' => $this->luckyNumber,
            'luckyColor' => $this->luckyColor,
            'luckyItem' => $this->luckyItem,
            'luckyDirection' => $this->luckyDirection,
            'matchSign' => $this->matchSign->value,
            'advice' => $this->advice,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function restore(array $snapshot): self
    {
        return new self(
            date: $snapshot['date'],
            sign: ZodiacSign::from($snapshot['sign']),
            sunSign: ZodiacSign::from($snapshot['sunSign']),
            moonSign: ZodiacSign::from($snapshot['moonSign']),
            ruler: $snapshot['ruler'],
            scores: $snapshot['scores'],
            luckyNumber: $snapshot['luckyNumber'],
            luckyColor: $snapshot['luckyColor'],
            luckyItem: $snapshot['luckyItem'],
            luckyDirection: $snapshot['luckyDirection'],
            matchSign: ZodiacSign::from($snapshot['matchSign']),
            advice: $snapshot['advice'],
        );
    }
}
