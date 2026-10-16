<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiRevisionOrigin: string
{
    case AiEdit = 'ai_edit';
    case Admin = 'admin';
    case Import = 'import';
    case Revert = 'revert';

    public function label(): string
    {
        return match ($this) {
            self::AiEdit => 'AI 協助編輯',
            self::Admin => '管理員編輯',
            self::Import => '匯入',
            self::Revert => '還原',
        };
    }
}
