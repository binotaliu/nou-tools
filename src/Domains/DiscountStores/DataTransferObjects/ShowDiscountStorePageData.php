<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class ShowDiscountStorePageData extends Data
{
    public function __construct(
        #[MapInputName('category')]
        public ?int $categoryId = null,
        public ?string $type = null,
        public ?string $search = null,
        #[MapInputName('city')]
        public ?string $city = null,
    ) {}
}
