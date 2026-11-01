<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The twelve signs in zodiac order, so a sign's position times 30 is the
 * ecliptic longitude where it starts.
 */
enum ZodiacSign: string
{
    case Aries = 'aries';
    case Taurus = 'taurus';
    case Gemini = 'gemini';
    case Cancer = 'cancer';
    case Leo = 'leo';
    case Virgo = 'virgo';
    case Libra = 'libra';
    case Scorpio = 'scorpio';
    case Sagittarius = 'sagittarius';
    case Capricorn = 'capricorn';
    case Aquarius = 'aquarius';
    case Pisces = 'pisces';

    public function label(): string
    {
        return match ($this) {
            self::Aries => '牡羊座',
            self::Taurus => '金牛座',
            self::Gemini => '雙子座',
            self::Cancer => '巨蟹座',
            self::Leo => '獅子座',
            self::Virgo => '處女座',
            self::Libra => '天秤座',
            self::Scorpio => '天蠍座',
            self::Sagittarius => '射手座',
            self::Capricorn => '摩羯座',
            self::Aquarius => '水瓶座',
            self::Pisces => '雙魚座',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Aries => '♈',
            self::Taurus => '♉',
            self::Gemini => '♊',
            self::Cancer => '♋',
            self::Leo => '♌',
            self::Virgo => '♍',
            self::Libra => '♎',
            self::Scorpio => '♏',
            self::Sagittarius => '♐',
            self::Capricorn => '♑',
            self::Aquarius => '♒',
            self::Pisces => '♓',
        };
    }

    public function dateRange(): string
    {
        return match ($this) {
            self::Aries => '3/21–4/19',
            self::Taurus => '4/20–5/20',
            self::Gemini => '5/21–6/21',
            self::Cancer => '6/22–7/22',
            self::Leo => '7/23–8/22',
            self::Virgo => '8/23–9/22',
            self::Libra => '9/23–10/23',
            self::Scorpio => '10/24–11/22',
            self::Sagittarius => '11/23–12/21',
            self::Capricorn => '12/22–1/19',
            self::Aquarius => '1/20–2/18',
            self::Pisces => '2/19–3/20',
        };
    }

    /**
     * 0 = fire, 1 = earth, 2 = air, 3 = water; the signs cycle through them.
     */
    public function element(): int
    {
        return $this->index() % 4;
    }

    public function index(): int
    {
        foreach (self::cases() as $position => $case) {
            if ($case === $this) {
                return $position;
            }
        }

        return 0;
    }

    public static function fromIndex(int $index): self
    {
        return self::cases()[(($index % 12) + 12) % 12];
    }
}
