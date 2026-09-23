<?php

declare(strict_types=1);

namespace App\Enums;

enum ChangelogPostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Published => '已發布',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getLabels(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $case): array {
            $carry[$case->value] = $case->label();

            return $carry;
        }, []);
    }
}
