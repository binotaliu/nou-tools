<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use App\Models\DiscountStore;
use NouTools\Domains\DiscountStores\ViewModels\DiscountStoreDetailViewModel;

final readonly class ShowDiscountStoreDetailPage
{
    public function __invoke(DiscountStore $store): DiscountStoreDetailViewModel
    {
        $store->load([
            'category',
            'reports' => fn ($query) => $query->latest()->limit(6),
            'comments' => fn ($query) => $query->where('is_approved', true)->latest(),
        ])->loadCount([
            'reports as valid_reports_count' => fn ($query) => $query->where('is_valid', true),
            'reports as invalid_reports_count' => fn ($query) => $query->where('is_valid', false),
            'comments' => fn ($query) => $query->where('is_approved', true),
        ]);

        return DiscountStoreDetailViewModel::fromModel($store);
    }
}
