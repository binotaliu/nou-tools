<?php

declare(strict_types=1);

namespace App\Enums;

enum CafeSeatKind: string
{
    case Bar = 'bar';
    case Window = 'window';
    case Communal = 'communal';

    public function label(): string
    {
        return match ($this) {
            self::Bar => '吧台',
            self::Window => '靠牆小桌',
            self::Communal => '大長桌',
        };
    }
}
