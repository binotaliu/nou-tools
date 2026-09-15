<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\ViewModels;

use App\Models\DiscountStoreComment;
use Spatie\LaravelData\Data;

/**
 * A single approved comment left on a discount store (優惠店家留言).
 */
final class DiscountStoreCommentViewModel extends Data
{
    public function __construct(
        public string $nickname,
        public string $content,
        public string $createdAtHuman,
    ) {}

    public static function fromModel(DiscountStoreComment $comment): self
    {
        return new self(
            nickname: $comment->nickname,
            content: $comment->content,
            createdAtHuman: $comment->created_at?->diffForHumans() ?? '',
        );
    }
}
