<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterSection: string
{
    case News = 'news';
    case Centers = 'centers';

    public function label(): string
    {
        return match ($this) {
            self::News => '空大新消息',
            self::Centers => '各中心消息',
        };
    }

    public static function forSourceGroup(AnnouncementSourceGroup $group): self
    {
        return $group === AnnouncementSourceGroup::Center ? self::Centers : self::News;
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
