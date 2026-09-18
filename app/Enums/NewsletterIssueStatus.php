<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterIssueStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Ready => '待發布',
            self::Published => '已發布',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Ready => 'warning',
            self::Published => 'success',
        };
    }
}
