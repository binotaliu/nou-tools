<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountStoreType: string
{
    case Online = 'online';
    case Chain = 'chain';
    case Local = 'local';

    public function label(): string
    {
        return match ($this) {
            self::Online => '線上',
            self::Chain => '連鎖',
            self::Local => '地區性',
        };
    }
}
