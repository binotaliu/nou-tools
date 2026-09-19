<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementSourceGroup: string
{
    case Administrative = 'administrative';
    case Center = 'center';
    case Department = 'department';

    /**
     * The group a source belongs to, per `announcements.source_groups`.
     * Unlisted sources count as 各處室.
     */
    public static function forSource(string $sourceName): self
    {
        $sourceGroups = config('announcements.source_groups', []);

        return self::tryFrom($sourceGroups[$sourceName] ?? '') ?? self::Administrative;
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrative => '各處室',
            self::Center => '學習指導中心',
            self::Department => '學系',
        };
    }
}
