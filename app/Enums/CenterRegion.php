<?php

declare(strict_types=1);

namespace App\Enums;

enum CenterRegion: string
{
    case North = 'north';
    case Central = 'central';
    case South = 'south';
    case East = 'east';
    case OffshoreIslands = 'offshore_islands';
    case Overseas = 'overseas';

    public function label(): string
    {
        return match ($this) {
            self::North => '北部',
            self::Central => '中部',
            self::South => '南部',
            self::East => '東部',
            self::OffshoreIslands => '離島',
            self::Overseas => '海外',
        };
    }
}
