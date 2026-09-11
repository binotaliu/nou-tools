<?php

declare(strict_types=1);

namespace App\Enums;

enum StudySeatKind: string
{
    case Solo = 'solo';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Solo => '單人座',
            self::Shared => '共享桌',
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
