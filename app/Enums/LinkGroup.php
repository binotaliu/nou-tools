<?php

declare(strict_types=1);

namespace App\Enums;

enum LinkGroup: string
{
    case Administrative = 'administrative';
    case Department = 'department';
    case Center = 'center';
    case Services = 'services';

    public function label(): string
    {
        return match ($this) {
            self::Administrative => '各處室',
            self::Department => '學系',
            self::Center => '學習指導中心',
            self::Services => '服務',
        };
    }
}
