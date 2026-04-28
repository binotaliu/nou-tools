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
            ->get();

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
