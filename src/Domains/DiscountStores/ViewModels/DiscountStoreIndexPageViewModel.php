<?php

namespace NouTools\Domains\DiscountStores\ViewModels;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class DiscountStoreIndexPageViewModel extends Data
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
