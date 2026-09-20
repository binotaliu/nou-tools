<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterReactionType: string
{
    case Like = 'like';
    case Love = 'love';
    case Helpful = 'helpful';
    case Celebrate = 'celebrate';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '👍',
            self::Love => '❤️',
            self::Helpful => '💡',
            self::Celebrate => '🎉',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Like => '讚',
            self::Love => '喜歡',
            self::Helpful => '有幫助',
            self::Celebrate => '太棒了',
        };
    }
}
