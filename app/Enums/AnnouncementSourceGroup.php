<?php

namespace App\Enums;

enum AnnouncementSourceGroup: string
{
    case Administrative = 'administrative';
    case Center = 'center';
    case Department = 'department';

    public function label(): string
    {
        return match ($this) {
            self::Administrative => '各處室',
            self::Center => '學習指導中心',
            self::Department => '學系',
        };
    }
}
