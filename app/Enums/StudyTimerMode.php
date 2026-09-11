<?php

declare(strict_types=1);

namespace App\Enums;

enum StudyTimerMode: string
{
    case Pomodoro = 'pomodoro';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Pomodoro => '番茄鐘',
            self::Custom => '自訂計時',
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
