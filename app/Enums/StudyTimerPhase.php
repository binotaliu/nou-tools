<?php

declare(strict_types=1);

namespace App\Enums;

enum StudyTimerPhase: string
{
    case Focus = 'focus';
    case Break = 'break';

    public function label(): string
    {
        return match ($this) {
            self::Focus => '專注中',
            self::Break => '休息中',
        };
    }

    public static function getLabels(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $case): array {
            $carry[$case->value] = $case->label();

            return $carry;
        }, []);
    }
}
