<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Enums\DiscountStoreStatus;
use App\Enums\NewsletterIssueStatus;
use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use App\Models\DiscountStoreReport;
use App\Models\NewsletterIssue;

final readonly class ResolveModerationQueueCounts
{
    /**
     * Items that are waiting for an admin to act.
     *
     * @return array{
     *     pendingStores: int,
     *     unapprovedComments: int,
     *     invalidReports: int,
     *     newsletterAwaitingPublish: int,
     * }
     */
    public function __invoke(): array
    {
        return [
            'pendingStores' => DiscountStore::query()->where('status', DiscountStoreStatus::Pending)->count(),
            'unapprovedComments' => DiscountStoreComment::query()->where('is_approved', false)->count(),
            'invalidReports' => DiscountStoreReport::query()->where('is_valid', false)->count(),
            'newsletterAwaitingPublish' => NewsletterIssue::query()->where('status', NewsletterIssueStatus::Ready)->count(),
        ];
    }
}
