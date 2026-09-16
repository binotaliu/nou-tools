<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\ViewModels;

use App\Models\DiscountStoreReport;
use Spatie\LaravelData\Data;

/**
 * A single validity report left on a discount store (優惠店家回報).
 */
final class DiscountStoreReportViewModel extends Data
{
    public function __construct(
        public bool $isValid,
        public ?string $comment,
        public string $createdAtHuman,
    ) {}

    public static function fromModel(DiscountStoreReport $report): self
    {
        return new self(
            isValid: $report->is_valid,
            comment: $report->comment,
            createdAtHuman: $report->created_at?->diffForHumans() ?? '',
        );
    }
}
