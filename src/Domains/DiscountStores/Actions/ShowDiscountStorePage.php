<?php

namespace NouTools\Domains\DiscountStores\Actions;

use App\Enums\DiscountStoreStatus;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use NouTools\Domains\DiscountStores\DataTransferObjects\ShowDiscountStorePageData;
use NouTools\Domains\DiscountStores\ViewModels\DiscountStoreIndexPageViewModel;

final readonly class ShowDiscountStorePage
{
    public function __construct(
        private LoadTaiwanRegions $loadTaiwanRegions,
    ) {}

    public function __invoke(ShowDiscountStorePageData $input): DiscountStoreIndexPageViewModel
    {
        $stores = DiscountStore::query()
            ->where('status', DiscountStoreStatus::Online)
            ->with(['category', 'latestReport'])
            ->orderByDesc('id')
            ->get()
            ->sort(function (DiscountStore $leftStore, DiscountStore $rightStore): int {
                $toPriority = static function (DiscountStore $store): int {
                    if ($store->latestReport?->is_valid === true) {
                        return 0;
                    }

                    if ($store->latestReport === null) {
                        return 1;
                    }

                    return 2;
                };

                $leftPriority = $toPriority($leftStore);
                $rightPriority = $toPriority($rightStore);

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                $leftTimestamp = $leftStore->latestReport?->created_at?->timestamp ?? 0;
                $rightTimestamp = $rightStore->latestReport?->created_at?->timestamp ?? 0;

                return $rightTimestamp <=> $leftTimestamp;
            })
            ->values();

        $categories = DiscountStoreCategory::query()
            ->orderBy('sort_order')
            ->get();

        $cities = collect(($this->loadTaiwanRegions)())
            ->pluck('name')
            ->values();

        return new DiscountStoreIndexPageViewModel(
            stores: $stores,
            categories: $categories,
            cities: $cities,
            selectedCategoryId: $input->categoryId,
            selectedType: $input->type,
            search: $input->search,
            selectedCity: $input->city,
        );
    }
}
