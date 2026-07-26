<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\ViewModels;

use App\Enums\DiscountStoreType;
use App\Models\DiscountStore;
use Spatie\LaravelData\Data;

/**
 * A discount store listing, including its category and latest validity report (優惠店家).
 */
final class DiscountStoreViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public DiscountStoreType $type,
        public ?int $categoryId,
        public ?string $city,
        public ?string $district,
        public ?string $address,
        public string $discountDetails,
        public ?string $verificationMethod,
        public ?DiscountStoreCategoryViewModel $category,
        public ?bool $latestReportIsValid,
        public ?string $latestReportCreatedAtDate,
        public ?string $latestReportCreatedAtDateTime,
    ) {}

    public static function fromModel(DiscountStore $store): self
    {
        return new self(
            id: $store->id,
            name: $store->name,
            type: $store->type,
            categoryId: $store->category_id,
            city: $store->city,
            district: $store->district,
            address: $store->address,
            discountDetails: $store->discount_details,
            verificationMethod: $store->verification_method,
            category: $store->category ? DiscountStoreCategoryViewModel::fromModel($store->category) : null,
            latestReportIsValid: $store->latestReport?->is_valid,
            latestReportCreatedAtDate: $store->latestReport?->created_at?->format('Y/m/d'),
            latestReportCreatedAtDateTime: $store->latestReport?->created_at?->format('Y-m-d H:i'),
        );
    }
}
