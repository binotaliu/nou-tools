<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiEditEventType: string
{
    case Requested = 'requested';
    case Refined = 'refined';
    case Redrafted = 'redrafted';
    case Published = 'published';
    case QueuedForReview = 'queued_for_review';

    /** The editor turned the request down. */
    case Declined = 'declined';

    /** The editor errored or its draft failed validation. */
    case Failed = 'failed';

    /** A refinement could not be carried out; the earlier draft stays. */
    case RefinementFailed = 'refinement_failed';

    case Conflict = 'conflict';
    case Rejected = 'rejected';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Requested => '提出修改',
            self::Refined => '再調整',
            self::Redrafted => '依最新版本重寫',
            self::Published => '已發布',
            self::QueuedForReview => '送交審核',
            self::Declined => 'AI 未採納',
            self::Failed => '處理失敗',
            self::RefinementFailed => '再調整失敗',
            self::Conflict => '版本衝突',
            self::Rejected => '已退回',
            self::Abandoned => '已放棄',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested, self::Refined, self::Redrafted => 'info',
            self::Published => 'success',
            self::QueuedForReview, self::Conflict => 'warning',
            self::Declined, self::Failed, self::RefinementFailed, self::Rejected => 'danger',
            self::Abandoned => 'gray',
        };
    }

    /**
     * Whether the outcome means the visitor's edit did not go through, which
     * is what the Discord webhook reports besides the review queue.
     */
    public function isUnsuccessful(): bool
    {
        return in_array($this, [self::Declined, self::Failed, self::RefinementFailed, self::Conflict, self::Rejected], true);
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
