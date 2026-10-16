<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiEditRisk: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => '低',
            self::Medium => '中',
            self::High => '高',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'success',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }
}
