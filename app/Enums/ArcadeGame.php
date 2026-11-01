<?php

declare(strict_types=1);

namespace App\Enums;

enum ArcadeGame: string
{
    case Horoscope = 'horoscope';

    public function label(): string
    {
        return match ($this) {
            self::Horoscope => '星座運勢',
        };
    }
}
