<?php

namespace App\Enums;

enum ArticleType: string
{
    case KNOWLEDGE_BASE = 'kb';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::KNOWLEDGE_BASE => '知識庫',
            self::MANUAL => '操作手冊',
        };
    }

    public function directory(): string
    {
        return match ($this) {
            self::KNOWLEDGE_BASE => 'kb',
            self::MANUAL => 'manual',
        };
    }
}
