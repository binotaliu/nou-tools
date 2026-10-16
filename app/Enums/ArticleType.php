<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleType: string
{
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => '操作手冊',
        };
    }

    public function directory(): string
    {
        return match ($this) {
            self::MANUAL => 'manual',
        };
    }
}
