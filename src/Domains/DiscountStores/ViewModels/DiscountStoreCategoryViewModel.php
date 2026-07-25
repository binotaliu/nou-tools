<?php

namespace NouTools\Domains\DiscountStores\ViewModels;

use App\Models\DiscountStoreCategory;
use Spatie\LaravelData\Data;

/**
 * A discount store category (優惠店家分類).
 */
final class DiscountStoreCategoryViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $icon,
        public int $sortOrder,
    ) {}

    public static function fromModel(DiscountStoreCategory $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            icon: $category->icon,
            sortOrder: $category->sort_order,
        );
    }
}
