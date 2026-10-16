<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiEditRequestStatus: string
{
    /** The editor is writing (or rewriting) the draft. */
    case Drafting = 'drafting';

    /** A draft is ready and waits for the person who asked for it. */
    case AwaitingConfirmation = 'awaiting_confirmation';

    /** The editor turned the request down; `ai_verdict.decline_reason` says why. */
    case DeclinedByAi = 'declined_by_ai';

    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';

    /** The page changed after the draft was written, so it no longer applies as is. */
    case Conflict = 'conflict';

    /** The editor errored or wrote something that failed validation. */
    case Failed = 'failed';

    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Drafting => '撰寫中',
            self::AwaitingConfirmation => '待確認',
            self::DeclinedByAi => '未採納',
            self::PendingReview => '等待審核',
            self::Published => '已發布',
            self::Rejected => '已退回',
            self::Conflict => '版本衝突',
            self::Failed => '處理失敗',
            self::Abandoned => '已放棄',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Drafting, self::AwaitingConfirmation => 'info',
            self::PendingReview, self::Conflict => 'warning',
            self::Published => 'success',
            self::DeclinedByAi, self::Rejected, self::Failed => 'danger',
            self::Abandoned => 'gray',
        };
    }

    /**
     * Whether the request can still change: anything else is a final state.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Drafting, self::AwaitingConfirmation, self::Conflict, self::PendingReview], true);
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
