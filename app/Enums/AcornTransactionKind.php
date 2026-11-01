<?php

declare(strict_types=1);

namespace App\Enums;

enum AcornTransactionKind: string
{
    case StudyFocus = 'study_focus';
    case LearningProgress = 'learning_progress';
    case Welcome = 'welcome';
    case CoffeePurchase = 'coffee_purchase';
    case StickyNotePurchase = 'sticky_note_purchase';
    case ArcadePlay = 'arcade_play';
    case Refund = 'refund';
    case AdminAdjust = 'admin_adjust';

    public function label(): string
    {
        return match ($this) {
            self::StudyFocus => '自習室專注',
            self::LearningProgress => '學習進度',
            self::Welcome => '歡迎禮',
            self::CoffeePurchase => '購買咖啡',
            self::StickyNotePurchase => '購買便利貼',
            self::ArcadePlay => '遊樂機台',
            self::Refund => '退還',
            self::AdminAdjust => '管理員調整',
        };
    }
}
