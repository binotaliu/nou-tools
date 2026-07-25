<?php

namespace NouTools\Domains\DiscountStores\PageData;

use Illuminate\Support\Collection;
use NouTools\Domains\DiscountStores\ViewModels\DiscountStoreCategoryViewModel;
use NouTools\Domains\DiscountStores\ViewModels\DiscountStoreViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class DiscountStoreIndexPageData extends Resource
{
    /**
     * @param  Collection<int, string>  $cities
     */
    public function __construct(
        #[DataCollectionOf(DiscountStoreViewModel::class)]
        public DataCollection $stores,
        #[DataCollectionOf(DiscountStoreCategoryViewModel::class)]
        public DataCollection $categories,
        public Collection $cities,
        public ?int $selectedCategoryId,
        public ?string $selectedType,
        public ?string $search,
        public ?string $selectedCity,
    ) {}
}
