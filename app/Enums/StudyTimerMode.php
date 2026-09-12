<?php

declare(strict_types=1);

namespace App\Enums;

enum StudyTimerMode: string
{
    case Pomodoro = 'pomodoro';
    case Custom = 'custom';
    case CountUp = 'count_up';

    public function label(): string
    {
        return match ($this) {
            self::Pomodoro => '番茄鐘',
            self::Custom => '倒數計時',
            self::CountUp => '正數計時',
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
