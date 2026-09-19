<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterSection: string
{
    case News = 'news';
    case Arts = 'arts';
    case Centers = 'centers';

    public function label(): string
    {
        return match ($this) {
            self::News => '空大新消息',
            self::Arts => '藝文活動',
            self::Centers => '各中心消息',
        };
    }

    public static function forSourceGroup(AnnouncementSourceGroup $group): self
    {
        return $group === AnnouncementSourceGroup::Center ? self::Centers : self::News;
    }

    /**
     * The section whose announcements a section draws its candidates from.
     * 藝文活動 is a topic rather than a source group, so it picks from the
     * same announcements as 空大新消息.
     */
    public function candidatePool(): self
    {
        return $this === self::Arts ? self::News : $this;
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
