<?php

declare(strict_types=1);

namespace App\Enums;

enum StickyNoteStatus: string
{
    // Posted and paid for; the AI pre-screen has not answered yet.
    case Screening = 'screening';
    case Published = 'published';
    // Held for an admin: the screen was unsure, the job died, or readers reported it.
    case PendingReview = 'pending_review';
    case Declined = 'declined';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Screening => '檢查中',
            self::Published => '已張貼',
            self::PendingReview => '待審核',
            self::Declined => '已退回',
            self::Hidden => '已隱藏',
        };
    }

    /**
     * Statuses only the author gets to see on the wall.
     */
    public function isPendingForAuthor(): bool
    {
        return $this === self::Screening || $this === self::PendingReview;
    }
}
